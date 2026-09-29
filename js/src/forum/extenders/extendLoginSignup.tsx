import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import LogInButtons from 'flarum/forum/components/LogInButtons';
import LogInButton from 'flarum/forum/components/LogInButton';
import extractText from 'flarum/common/utils/extractText';
import Tooltip from 'flarum/common/components/Tooltip';

import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';

export type OAuthProvider = {
  name: string;
  icon: string;
  priority: number;
} | null;

/**
 * Build the OAuth authorization URL for a given provider path.
 * Appends returnTo so the server can redirect back after auth.
 */
function oauthUrl(path: string): string {
  const base = app.forum.attribute<string>('baseUrl');
  const returnTo = encodeURIComponent(window.location.pathname + window.location.search);
  return `${base}${path}?returnTo=${returnTo}`;
}

export default function () {
  // Replace the popup onclick with a full-page navigation.
  override(LogInButton, 'initAttrs', function (original, attrs) {
    original(attrs);

    // Override whatever onclick the parent set — we navigate instead of opening a popup.
    attrs.onclick = function (e: MouseEvent) {
      e.preventDefault();
      window.location.href = oauthUrl(attrs.path);
    };
  });

  extend(LogInButtons.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    const onlyIcons = app.forum.attribute<boolean>('fof-oauth.only_icons');
    const enabledOAuthProviders =
      app.forum.attribute<OAuthProvider[]>('fof-oauth')?.filter((provider): provider is NonNullable<OAuthProvider> => provider !== null) ?? [];

    enabledOAuthProviders.forEach(({ name, icon, priority }) => {
      // Signing in with this provider again would only repeat the unverified email notice.
      if (name === app.fof_oauth_unverifiedEmailProvider) return;

      let className = `Button FoFLogInButton LogInButton--${name}`;

      if (onlyIcons) {
        className += ' Button--icon';
      }

      items.add(
        name,
        <div className={`LogInButtonContainer LogInButtonContainer--${name}`}>
          <LogInButton className={className} icon={icon} path={`/auth/${name}`}>
            {app.translator.trans(`fof-oauth.forum.log_in.with_${name}_button`, {
              provider: app.translator.trans(`fof-oauth.forum.providers.${name}`),
            })}
          </LogInButton>
        </div>,
        priority
      );
    });
  });

  override(LogInButton.prototype, 'view', function (original, vnode: Mithril.VnodeDOM) {
    const onlyIcons = app.forum.attribute<boolean>('fof-oauth.only_icons');
    if (!onlyIcons) return original(vnode);

    const child = original(vnode);

    // @ts-ignore
    return <Tooltip text={extractText(child.children[1])}>{child}</Tooltip>;
  });

  extend(LogInButtons.prototype, 'view', function (vdom) {
    const onlyIcons = app.forum.attribute<boolean>('fof-oauth.only_icons');
    if (!onlyIcons) return;

    // @ts-ignore
    vdom.attrs.className += ' FoFLogInButtons--icons';
  });

  extend('flarum/forum/components/SignUpModal', 'fields', function (items: ItemList<unknown>) {
    // If a suggested username was not provided by the OAuth service, display help text.
    if (!!this.attrs.token && !this.attrs.username) {
      items.add(
        'username-help',
        <div>
          <p>{app.translator.trans('fof-oauth.forum.signup.username_help')}</p>
        </div>,
        35
      );
    }
  });

  // LogInModal is lazy loaded in 2.x, so it is extended by module path.
  extend('flarum/forum/components/LogInModal', 'oninit', function (this: any) {
    const provider = this.attrs.fofOAuthUnverifiedEmailProvider;

    if (provider) {
      app.fof_oauth_unverifiedEmailProvider = provider;

      this.alertAttrs = {
        type: 'warning',
        content: app.translator.trans('fof-oauth.forum.log_in.unverified_email_in_use', {
          provider: app.translator.trans(`fof-oauth.forum.providers.${provider}`),
        }),
      };
    }
  });

  extend('flarum/forum/components/LogInModal', 'onremove', function (this: any) {
    if (this.attrs.fofOAuthUnverifiedEmailProvider) {
      app.fof_oauth_unverifiedEmailProvider = undefined;
    }
  });
}
