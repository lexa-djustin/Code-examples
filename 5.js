(function (w, $) {
    /**
     *
     * @param object $form
     * @param object options
     * @constructor
     */
    function AjaxForm($form) {
        this.$form = $form;
        this.validators = {};
        this.messages = {};
        this.isValidResult = true;
        this.values = {};
        this.filters = {};
        this.sending = false;

        this.options = {
            onSendSuccess: function () {},
            onSendError: function () {}
        };

        this.observer = new AjaxFormObserver(this);
    }

    /**
     * @param {string} name
     * @param {function} validator
     * @param {string} message
     */
    AjaxForm.prototype.addValidator = function (name, validator, message) {
        if (typeof name !== 'string') {
            throw new TypeError('First argument must be a string type');
        } else if (typeof validator !== 'function') {
            throw new TypeError('Second argument must be a function type');
        } else if (typeof message !== 'string') {
            throw new TypeError('Third argument must be a string type');
        }

        var self = this;

        if (!(name in this.validators)) {
            self.validators[name] = [];
        }

        self.validators[name].push({
            callback: validator,
            message: message
        });
    };

    /**
     * @param {string} name
     * @param {function} filter
     *
     * @return {this}
     */
    AjaxForm.prototype.addFilter = function (name, filter) {
        if (typeof name !== 'string') {
            throw new TypeError('First argument must be a string type');
        } else if (typeof filter !== 'function') {
            throw new TypeError('Second argument must be a function type');
        }

        var self = this;

        if (!(name in this.validators)) {
            self.filters[name] = [];
        }

        this.filters[name].push(filter);

        return this;
    };

    /**
     * @return {object}
     */
    AjaxForm.prototype.getMessages = function () {
        return this.messages;
    };

    /**
     * @private
     */
    AjaxForm.prototype._prepareValues = function () {
        var values = this.$form.serializeArray(),
            self = this
        ;

        $.each(values, function () {
            self.values[this.name] = this.value;
        });
    };

    /**
     * @private
     */
    AjaxForm.prototype._filter = function () {
        var self = this;

        $.each(this.values, function (name, value) {
            if (!(name in self.filters)) {
                return;
            }

            var filters = self.filters[name],
                len = filters.length,
                i
            ;

            for (i = 0; i < len; i++) {
                self.values[name] = filters[i](value);
            }
        });
    };

    /**
     * @private
     */
    AjaxForm.prototype._validate = function () {
        var self = this;

        $.each(this.values, function (name, value) {
            if (!(name in self.validators)) {
                return;
            }

            var validators = self.validators[name],
                result,
                len = validators.length,
                i
            ;

            for (i = 0; i < len; i++) {
                result = validators[i].callback(value);

                if (!result) {
                    self.isValidResult = false;
                    self.messages[name] = validators[i].message;
                    return;
                }
            }
        });
    };

    /**
     * @return {boolean}
     */
    AjaxForm.prototype.isValid = function () {
        this._reset();
        this._prepareValues();
        this._filter();
        this._validate();

        return this.isValidResult;
    };

    AjaxForm.prototype.send = function () {
        var self = this;

        if (this.sending) {
            return;
        }

        this.sending = true;

        $.ajax({
            method: this.$form.prop('method'),
            url: this.$form.prop('action'),
            data: this.values,
            dataType: 'json',
            success: function (response, textStatus) {
                if (response.success === true) {
                    self.observer.trigger('onSendSuccess', [self.$form, textStatus, response]);
                    self.clear();
                } else {
                    self.observer.trigger('onSendError', [self.$form, textStatus, response]);
                }
            },
            error: function (jqXHR, textStatus) {
                self.observer.trigger('onSendError', [self.$form, textStatus, null]);
            },
            complete: function () {
                self.sending = false;
            }
        });
    };

    AjaxForm.prototype.clear = function () {
        this.$form.find('input, select').val('');
    };

    /**
     * @private
     */
    AjaxForm.prototype._reset = function () {
        this.messages = {};
        this.isValidResult = true;
    };

    /**
     * @param {string} name
     * @param {function} listener
     *
     * @return {AjaxForm}
     */
    AjaxForm.prototype.addListener = function (name, listener) {
        this.observer.addListener(name, listener);

        return this;
    };

    /**
     * Наблюдатель за событиями формы
     *
     * @param {string} context
     *
     * @constructor
     */
    function AjaxFormObserver(context) {
        this.context = context;
        this.listeners = {};
    }

    /**
     * @param {string} name
     * @param {function} listener
     * 
     * @return {AjaxFormObserver}
     */
    AjaxFormObserver.prototype.addListener = function (name, listener) {
        if (typeof name !== 'string') {
            throw new TypeError('First argument must be a string type');
        } else if (typeof listener !== 'function') {
            throw new TypeError('Second argument must be a function type');
        }

        if (!(name in this.listeners)) {
            this.listeners[name] = [];
        }

        this.listeners[name].push(listener);

        return this;
    };

    /**
     * @param {string} name
     * @param {array} arguments
     *
     * @return {AjaxFormObserver}
     */
    AjaxFormObserver.prototype.trigger = function (name, arguments) {
        if (!(name in this.listeners)) {
            return this;
        }

        arguments = arguments || [];

        var listeners = this.listeners[name],
            len = listeners.length,
            i;

        for (i = 0; i < len; i++) {
            listeners[i].apply(this.context, arguments);
        }

        return this;
    };

    w.AjaxForm = AjaxForm;
}(window, jQuery));