/**
 * JavaScript for form editing path conditions.
 *
 * @module moodle-availability_path-form
 */
M.availability_path = M.availability_path || {};

/**
 * @class M.availability_path.form
 * @extends M.core_availability.plugin
 */
M.availability_path.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} cms Array of objects containing cmid => name
 */
M.availability_path.form.initInner = function(cms) {
    this.cms = cms;
};

M.availability_path.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="col-form-label pr-3"> ' + M.util.get_string('title', 'availability_path') + '</span>' +
        ' <span class="availability-group form-group"><label>' +
        '<span class="sr-only">' + M.util.get_string('label_cm', 'availability_path') + '</span>' +
        '<select class="custom-select" name="cm" title="' + M.util.get_string('label_cm', 'availability_path') + '">' +
        '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.cms.length; i++) {
        var cm = this.cms[i];
        // String has already been escaped using format_string.
        html += '<option value="' + cm.id + '">' + cm.name + '</option>';
    }

    html += ' </select></label><label><span class="sr-only">' + M.util.get_string('label_option', 'availability_path') + '</span>' +
        '<select name="o" class="custom-select">' +
        '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    html += '</select></label></span>';

    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    var updateOptions = function(cmNode, optionNode, callback) {
        var cmid = cmNode.get('value');
        var url = M.cfg.wwwroot + '/availability/condition/path/ajax.php?cmid=' + cmid;
        // First, remove all options except the first one from the question drop-down menu.
        optionNode.all('option').each(function(optionNode) {
            if (optionNode.get('value') !== '') {
                optionNode.remove();
            }
        }, this);

        if (cmid) {
            // Disable the quiz element until we finish loading it's questions.
            cmNode.set('disabled', true);
            var pendingKey = {};
            M.util.js_pending(pendingKey);
            Y.io(url, {
                on: {
                    success: function(id, response) {
                        var questions = Y.JSON.parse(response.responseText);
                        for (var i = 0; i < questions.length; i++) {
                            var questionOption = document.createElement('option');
                            questionOption.value = questions[i].id;
                            questionOption.innerHTML = questions[i].title;
                            optionNode.append(questionOption);
                        }
                        // Questions are loaded, so we enable the quiz element now.
                        cmNode.set('disabled', false);

                        if (callback !== undefined) {
                            callback();
                        }

                        M.core_availability.form.update();
                        M.util.js_complete(pendingKey);
                    },
                    failure: function(id, response) {
                        // Loading failed. Let's enable the quiz so the user can try again.
                        cmNode.set('disabled', false);
                        M.util.js_complete(pendingKey);

                        var debugInfo = response.statusText;
                        if (M.cfg.developerdebug) {
                            debugInfo += ' (' + url + ')';
                        }
                        new M.core.exception({message: debugInfo});
                    }
                }
            });
        }
    };

    // Set initial values.
    if (json.cm !== undefined &&
        node.one('select[name=cm] > option[value=' + json.cm + ']')) {
        node.one('select[name=cm]').set('value', '' + json.cm);

        updateOptions(node.one('select[name=cm]'), node.one('select[name=o]'), function() {
            if (json.o !== undefined &&
                node.one('select[name=o] > option[value=' + json.o + ']')) {
                node.one('select[name=o]').set('value', '' + json.o);
            }
        });
    }

    // Add event handlers (first time only).
    if (!M.availability_path.form.addedEvents) {
        M.availability_path.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_path select');
        root.delegate('change', function() {
            var ancestorNode = this.ancestor('span.availability_path');
            var cmNode = ancestorNode.one('select[name=cm]');
            var optionidNode = ancestorNode.one('select[name=o]');

            updateOptions(cmNode, optionidNode);
        }, '.availability_path select[name=cm]');
    }

    return node;
};

M.availability_path.form.fillValue = function(value, node) {
    value.cm = parseInt(node.one('select[name=cm]').get('value'), 10);
    value.o = parseInt(node.one('select[name=o]').get('value'), 10);
};

M.availability_path.form.fillErrors = function(errors, node) {
    var cmid = parseInt(node.one('select[name=cm]').get('value'), 10);
    if (cmid === 0) {
        errors.push('availability_path:error_selectcmid');
    }

    var optionid = parseInt(node.one('select[name=o]').get('value'), 10);
    if (optionid === 0) {
        errors.push('availability_path:error_selectoptionid');
    }
};
