import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import icon from 'flarum/common/helpers/icon';
import Button from 'flarum/common/components/Button';
import LogInButton from 'flarum/forum/components/LogInButton';
import humanTime from 'flarum/common/utils/humanTime';

import type Mithril from 'mithril';
import LinkedAccount from '../models/LinkedAccount';
import User from 'flarum/common/models/User';
import extractText from 'flarum/common/utils/extractText';

interface IAttrs {
  provider: LinkedAccount;
  className?: string;
  class?: string;
  user: User;
}

interface IState {
  loading: boolean;
}

export default class LinkStatus extends Component<IAttrs, IState> {
  state = {
    loading: false,
  };

  view(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    const { provider, user } = this.attrs;
    const className = `Button FoFLogInButton LogInButton--${provider.name()} LogInButton${provider.linked() ? '--linked' : '--unlinked'}`;

    return (
      <li className={`LinkedAccounts-Account LinkedAccounts-Account--${provider.name()}`}>
        <legend>{provider.orphaned() ? provider.name() : app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`)}</legend>

        {provider.orphaned() && (
          <p className="LinkedAccounts-AccountOrphaned helpText">
            {icon('fas fa-exclamation-circle')}
            <span>{app.translator.trans('fof-oauth.forum.user.settings.linked-account.orphaned-account')}</span>
          </p>
        )}

        <ul className="Provider-Info">
          <li className={`Provider-Icon Provider-Icon--${provider.name()}`}>{icon(provider.icon())}</li>
          {provider.linked() ? (
            [
              <li>
                <label>{app.translator.trans('fof-oauth.forum.user.settings.linked-account.link-created-label')}</label>
                <p>{humanTime(provider.firstLogin())}</p>
              </li>,
              <li>
                <label>{app.translator.trans('fof-oauth.forum.user.settings.linked-account.last-used-label')}</label>
                <p>{humanTime(provider.lastLogin())}</p>
              </li>,
              <li>
                <label>
                  {app.translator.trans('fof-oauth.forum.user.settings.linked-account.identifier-label', {
                    provider: provider.orphaned() ? provider.name() : app.translator.trans(`fof-oauth.lib.providers.${provider.name()}`),
                  })}
                </label>
                <p>
                  <code>{provider.providerIdentifier()}</code>
                </p>
              </li>,
              <li>
                <Button className={className} icon={provider.icon()} onclick={() => this.deleteProvider(provider)} loading={this.state.loading}>
                  {app.translator.trans('fof-oauth.forum.unlink')}
                </Button>
              </li>,
            ]
          ) : (
            <li>
              <LogInButton className={className} icon={provider.icon()} path={`/auth/${provider.name()}?linkTo=${user.id()}`}>
                {app.translator.trans(`fof-oauth.forum.log_in.with_${provider.name()}_button`, {
                  provider: app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`),
                })}
              </LogInButton>
            </li>
          )}
        </ul>
      </li>
    );
  }

  async deleteProvider(provider: LinkedAccount) {
    if (
      confirm(
        extractText(
          app.translator.trans('fof-oauth.forum.user.settings.linked-account.unlink-confirm', {
            provider: app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`),
          })
        )
      )
    ) {
      this.state.loading = true;
      await provider.delete();
      await app.store.find<LinkedAccount[]>('linked-accounts', {});
      this.state.loading = false;
      m.redraw();
    }
  }
}
