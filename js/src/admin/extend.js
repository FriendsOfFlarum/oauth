import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import AuthSettingsPage from './components/AuthSettingsPage';

export default [
  new Extend.Admin().page(AuthSettingsPage).permission(
    () => ({
      icon: 'fas fa-sign-in-alt',
      label: app.translator.trans('fof-oauth.admin.permissions.moderate_user_providers'),
      permission: 'moderateUserProviders',
    }),
    'moderate'
  ),
];
