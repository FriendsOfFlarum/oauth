import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import icon from 'flarum/common/helpers/icon';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import LinkedAccount from '../models/LinkedAccount';
import User from 'flarum/common/models/User';
import ProviderInfo from './ProviderInfo';
import extractText from 'flarum/common/utils/extractText';

interface IAttrs extends ComponentAttrs {
  provider: LinkedAccount;
  user: User;
}

export default class LinkStatus extends Component<IAttrs> {
  state = {
    loading: false,
  };

  onbeforeupdate(vnode: Mithril.Vnode<IAttrs, this>) {
    super.onbeforeupdate(vnode);
    if (app.fof_oauth_linkingInProgress && app.fof_oauth_linkingProvider === this.attrs.provider.name()) {
      this.state.loading = true;
    } else if (app.fof_oauth_linkingInProgress === false && app.fof_oauth_linkingProvider === this.attrs.provider.name()) {
      this.state.loading = false;
      delete app.fof_oauth_linkingInProgress;
      delete app.fof_oauth_linkingProvider;
    }
  }

  view(vnode: Mithril.Vnode<IAttrs, this>): Mithril.Children {
    return (
      <div className={`LinkedAccounts-Account LinkedAccounts-Account--${this.attrs.provider.name()}`}>
        {this.iconView()}
        {this.statusView()}
        {this.actionView()}
      </div>
    );
  }

  iconView() {
    return (
      <div className="LinkedAccountsList-item-icon">
        {icon(this.attrs.provider.icon(), { className: `Provider-Icon Provider-Icon--${this.attrs.provider.name()}` })}
      </div>
    );
  }

  statusView() {
    const provider = this.attrs.provider;

    return <ProviderInfo provider={provider} />;
  }

  actionView() {
    const provider = this.attrs.provider;
    const user = this.attrs.user;

    if (provider.linked()) {
      return (
        <div className="LinkedAccountsList-item-actions">
          <Button
            className={`Button FoFLogInButton LogInButton--${provider.name()} LogInButton${provider.linked() ? '--linked' : '--unlinked'}`}
            icon={provider.icon()}
            onclick={() => this.deleteProvider(provider)}
            loading={this.state.loading}
          >
            {app.translator.trans('fof-oauth.forum.unlink')}
          </Button>
        </div>
      );
    } else if (!provider.orphaned() && (user.id() === app.session.user?.id() || !app.forum.attribute<boolean>('fofOauthModerate'))) {
      return (
        <div className="LinkedAccountsList-item-actions">
          <Button
            className={`Button FoFLogInButton LogInButton--${provider.name()}`}
            icon={provider.icon()}
            path={`/auth/${provider.name()}?linkTo=${user.id()}`}
            loading={this.state.loading}
          >
            {app.translator.trans(`fof-oauth.forum.log_in.with_${provider.name()}_button`, {
              provider: app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`),
            })}
          </Button>
        </div>
      );
    }
    return null;
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
      await app.store.find<LinkedAccount[]>('users/' + this.attrs.user.id() + '/linked-accounts', {});
      this.state.loading = false;
      m.redraw();
    }
  }
}
