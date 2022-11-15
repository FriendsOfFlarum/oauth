import app from 'flarum/admin/app';

import AuthSettingsPage from './components/AuthSettingsPage';
import ConfigureWithOAuthPage from './components/ConfigureWithOAuthPage';

app.initializers.add('fof/oauth', () => {
  app.extensionData.for('fof-oauth').registerPage(AuthSettingsPage);
});

export { AuthSettingsPage, ConfigureWithOAuthPage };
