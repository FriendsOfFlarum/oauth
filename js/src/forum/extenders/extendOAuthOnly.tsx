import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';

import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';

export function oauthOnly(): boolean {
  return !!app.forum.attribute<boolean>('fofOauthOnly');
}

/**
 * OAuth-only mode: members log in and sign up through providers. The server rejects password
 * login, registration and reset for non-admins; this hides the controls that would only fail.
 */
export default function () {
  extend(HeaderSecondary.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    if (oauthOnly()) items.remove('signUp');
  });

  // LogInModal, SignUpModal and SettingsPage are lazy loaded in 2.x, so they are extended by module path.
  extend('flarum/forum/components/LogInModal', 'fields', function (this: any, items: ItemList<Mithril.Children>) {
    if (!oauthOnly() || this.fofOauthShowPasswordLogin) return;

    ['identification', 'password', 'remember', 'submit'].forEach((key) => items.remove(key));
  });

  // Replaced rather than extended: core's footer is a fragment, and both of its links (forgot password,
  // sign up) must go. Admins reveal the password form here, and only then get the forgot password link.
  override('flarum/forum/components/LogInModal', 'footer', function (this: any, original: () => Mithril.Children) {
    if (!oauthOnly()) return original();

    if (this.fofOauthShowPasswordLogin) {
      return (
        <p className="LogInModal-forgotPassword">
          <Button className="Button Button--text Button--link" onclick={this.forgotPassword.bind(this)}>
            {app.translator.trans('core.forum.log_in.forgot_password_link')}
          </Button>
        </p>
      );
    }

    return (
      <p className="LogInModal-adminLogIn">
        <Button className="Button Button--text Button--link" onclick={() => (this.fofOauthShowPasswordLogin = true)}>
          {app.translator.trans('fof-oauth.forum.log_in.admin_log_in_link')}
        </Button>
      </p>
    );
  });

  extend('flarum/forum/components/SignUpModal', 'fields', function (this: any, items: ItemList<Mithril.Children>) {
    // With a token the user is completing a provider sign-up, which stays allowed.
    if (!oauthOnly() || this.attrs.token) return;

    ['username', 'email', 'password', 'submit'].forEach((key) => items.remove(key));
  });

  extend('flarum/forum/components/SettingsPage', 'accountItems', function (items: ItemList<Mithril.Children>) {
    if (!oauthOnly() || app.session.user?.isAdmin()) return;

    // Both need a password: changing it goes through a reset email, changing email asks for the current one.
    items.remove('changePassword');
    items.remove('changeEmail');
  });
}
