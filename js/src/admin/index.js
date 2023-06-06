import app from 'flarum/admin/app';

import AuthSettingsPage from './components/AuthSettingsPage';
import ConfigureWithOAuthPage from './components/ConfigureWithOAuthPage';
import ConfigureWithOAuthButton from './components/ConfigureWithOAuthButton';

app.initializers.add('fof/oauth', () => {
  app.extensionData.for('fof-oauth').registerPage(AuthSettingsPage);
});

export { AuthSettingsPage, ConfigureWithOAuthPage, ConfigureWithOAuthButton };
