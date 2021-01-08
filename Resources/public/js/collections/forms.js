/**
 * Generated by https://github.com/alexander-schranz/sulu-backend-bundle.
 */

define([
    'mvc/collection',
    'suluform/model/form'
], function (Collection, Model) {

    return Collection({

        model: Model,

        url: function () {
            return '/admin/api/forms';
        },

        fieldsUrl: function () {
            return '/admin/api/forms/fields';
        },

        parse: function (resp) {
            return resp._embedded.forms;
        }
    });
});