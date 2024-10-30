import app from 'flarum/admin/app';
import AuthSettingsPage from './components/AuthSettingsPage';
import ConfigureWithOAuthPage from './components/ConfigureWithOAuthPage';
import ConfigureWithOAuthButton from './components/ConfigureWithOAuthButton';

app.initializers.add('fof/oauth', () => {
  app.extensionData
    .for('fof-oauth')
    .registerPage(AuthSettingsPage)
    .registerPermission(
      {
        icon: 'fas fa-sign-in-alt',
        label: app.translator.trans('fof-oauth.admin.permissions.moderate_user_providers'),
        permission: 'moderateUserProviders',
      },
      'moderate'
    );
});

export { AuthSettingsPage, ConfigureWithOAuthPage, ConfigureWithOAuthButton };
