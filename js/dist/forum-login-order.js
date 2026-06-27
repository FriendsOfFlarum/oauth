(function () {
  function defaultExport(module) {
    return module && module.__esModule ? module.default : module;
  }

  var providerIcons = {
    discord: 'fab fa-discord',
    facebook: 'fab fa-facebook-f',
    github: 'fab fa-github',
    gitlab: 'fab fa-gitlab',
    google: 'fab fa-google',
    linkedin: 'fab fa-linkedin-in',
    twitter: 'fab fa-twitter',
  };

  function providerNameFromClass(className) {
    var match = className && className.match(/LogInButton--([A-Za-z0-9_-]+)/);
    return match ? match[1] : null;
  }

  var app = defaultExport(flarum.core.compat['forum/app']);
  var extension = flarum.core.compat['common/extend'];
  var LogInButtons = defaultExport(flarum.core.compat['forum/components/LogInButtons']);
  var LogInButton = defaultExport(flarum.core.compat['forum/components/LogInButton']);
  var LogInModal = defaultExport(flarum.core.compat['forum/components/LogInModal']);
  var SignUpModal = defaultExport(flarum.core.compat['forum/components/SignUpModal']);

  app.initializers.add('fof/oauth-login-order', function () {
    extension.extend(LogInButton, 'initAttrs', function (_, attrs) {
      if (attrs.className && attrs.className.indexOf('FoFLogInButton') !== -1) {
        var providerName = providerNameFromClass(attrs.className);

        if (providerName && providerIcons[providerName]) {
          attrs.icon = providerIcons[providerName];
        }

        if (attrs.className.indexOf('Button--block') === -1) {
          attrs.className = attrs.className.replace('Button ', 'Button Button--block ');
        }
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
