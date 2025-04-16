import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import LogInButtons from 'flarum/forum/components/LogInButtons';
import LogInButton from 'flarum/forum/components/LogInButton';
import extractText from 'flarum/common/utils/extractText';
import Tooltip from 'flarum/common/components/Tooltip';
import LogInModal from 'flarum/forum/components/LogInModal';
import SignUpModal from 'flarum/forum/components/SignUpModal';
import ForumApplication from 'flarum/forum/ForumApplication';
import { openOAuthPopup } from '../utils/popupUtils';

import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';

export type OAuthProvider = {
  name: string;
  icon: string;
  priority: number;
} | null;

export default function () {
  extend(LogInButton, 'initAttrs', function (_returnedValue, attrs) {
    attrs.onclick = function () {
      openOAuthPopup(app, attrs);
    };
  });

  extend(LogInButtons.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    const onlyIcons = !!app.forum.attribute('fof-oauth.only_icons');
    const enabledOAuthProviders = app.forum.attribute<OAuthProvider[]>('fof-oauth').filter(Boolean);

    enabledOAuthProviders.forEach(({ name, icon, priority }) => {
      let className = `Button FoFLogInButton LogInButton--${name}`;

      // Google branding does not allow inline icon, so we'll keep the full button
      if (onlyIcons) {
        className += ' Button--icon';
      }

      items.add(
        name,
        <div className={`LogInButtonContainer LogInButtonContainer--${name}`}>
          <LogInButton className={className} icon={icon} path={`/auth/${name}`} disabled={app.fof_oauth_loginInProgress}>
            {app.translator.trans(`fof-oauth.forum.log_in.with_${name}_button`, {
              provider: app.translator.trans(`fof-oauth.forum.providers.${name}`),
            })}
          </LogInButton>
        </div>,
        priority
      );
    });
  });

  override(LogInButton.prototype, 'view', function (orig, vnode) {
    const onlyIcons = !!app.forum.attribute('fof-oauth.only_icons');
    if (!onlyIcons) return orig(vnode);

    const child = orig(vnode);

    return <Tooltip text={extractText(child.children[1])}>{child}</Tooltip>;
  });

  extend(LogInButtons.prototype, 'view', function (vdom) {
    const onlyIcons = !!app.forum.attribute('fof-oauth.only_icons');
    if (!onlyIcons) return;

    vdom.attrs.className += ' FoFLogInButtons--icons';
  });

  extend(ForumApplication.prototype, 'authenticationComplete', function (_, payload) {
    if (payload.loggedIn) {
      app.fof_oauth_loginInProgress = true;
      // This will automatically be reset, as authenticationComplete also triggers a window reload.

      m.redraw();
    }
  });

  ForumApplication.prototype.linkingComplete = async function () {
    try {
      app.fof_oauth_linkingInProgress = true;
      m.redraw();

      // Refresh the list of providers
      const newProviders = await this.store.find('linked-accounts');

      // The store will contain an old version of the login provider (unlinked) that has
      // another ID than the new one (linked). We need to delete that one from the store
      // so that the UI gets updated correctly.

      // Find the old provider (one that has the same name as one of the new providers & has providerIdentifier===null)
      const oldProvider = this.store
        .all('linked-accounts')
        // Find the providers that have not yet been linked (providerIdentifier is null)
        .filter((p) => p.providerIdentifier() === null)
        // Match it with one of the newly fetched providers by name
        .find((p) => newProviders.some((np) => np.name() === p.name()));

      if (oldProvider) {
        // @ts-ignore
        delete this.store.data['linked-accounts'][oldProvider.id()];
      } else {
        window.location.reload();
        return;
      }

      const userId = app.session?.user?.id();

      if (!userId) throw new Error('User ID not available');

      // Refresh the session user
      await this.store.find('users', userId);

      app.fof_oauth_linkingInProgress = false;
      m.redraw();
    } catch (error) {
      app.fof_oauth_linkingInProgress = false;
      m.redraw();
    }
  };

  extend(LogInModal.prototype, 'onbeforeupdate', function () {
    if (app.fof_oauth_loginInProgress) {
      // @ts-ignore
      this.loading = true;
    }
  });

  extend(SignUpModal.prototype, 'onbeforeupdate', function () {
    if (app.fof_oauth_loginInProgress) {
      // @ts-ignore
      this.loading = true;
    }
  });

  extend(SignUpModal.prototype, 'fields', function (items: ItemList<unknown>) {
    // If a suggested username was not provided by the OAuth service, display some help text to the user.
    if (!!this.attrs.token && !!!this.attrs.username) {
      items.add(
        'username-help',
        <div>
          <p>{app.translator.trans('fof-oauth.forum.signup.username_help')}</p>
        </div>,
        35
      );
    }
  });
}
