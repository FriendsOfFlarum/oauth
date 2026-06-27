(function () {
  if (!flarum.core) {
    flarum.core = {};
  }

  if (!flarum.core.compat) {
    flarum.core.compat = {};
  }

  function extend(target, method, callback) {
    var original = target && target[method];

    target[method] = function () {
      var result = original && original.apply(this, arguments);
      callback.apply(this, [result].concat(Array.prototype.slice.call(arguments)));
      return result;
    };
  }

  function override(target, method, callback) {
    var original = target && target[method];

    target[method] = function () {
      return callback.apply(this, [original ? original.bind(this) : function () {}].concat(Array.prototype.slice.call(arguments)));
    };
  }

  var extension = flarum.core.compat['common/extend'] || flarum.core.compat['flarum/common/extend'] || {};

  extension.extend = extension.extend || extend;
  extension.override = extension.override || override;

  flarum.core.compat['common/extend'] = extension;
  flarum.core.compat['flarum/common/extend'] = extension;
})();
