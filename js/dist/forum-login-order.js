(function () {
  var app = flarum.core.app;
  var extension = flarum.core.compat['common/extend'];
  var LogInButtons = flarum.core.compat['forum/components/LogInButtons'];
  var LogInButton = flarum.core.compat['forum/components/LogInButton'];
  var LogInModal = flarum.core.compat['forum/components/LogInModal'];
  var SignUpModal = flarum.core.compat['forum/components/SignUpModal'];

  app.initializers.add('fof/oauth-login-order', function () {
    extension.extend(LogInButton, 'initAttrs', function (_, attrs) {
      if (attrs.className && attrs.className.indexOf('FoFLogInButton') !== -1 && attrs.className.indexOf('Button--block') === -1) {
        attrs.className = attrs.className.replace('Button ', 'Button Button--block ');
      }
    });

    extension.override(LogInModal.prototype, 'body', function () {
      return [m('div', { className: 'Form Form--centered' }, this.fields().toArray()), m(LogInButtons)];
    });

    extension.override(SignUpModal.prototype, 'body', function () {
      return [m('div', { className: 'Form Form--centered' }, this.fields().toArray()), !this.attrs.token && m(LogInButtons)];
    });
  });
})();
