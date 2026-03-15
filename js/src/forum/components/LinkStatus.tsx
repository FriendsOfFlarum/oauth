import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import LinkedAccount from '../models/LinkedAccount';
import User from 'flarum/common/models/User';
import ProviderInfo from './ProviderInfo';
import extractText from 'flarum/common/utils/extractText';
import Icon from 'flarum/common/components/Icon';

export interface ILinkStatusAttrs extends ComponentAttrs {
  provider: LinkedAccount;
  user: User;
  refresh?: () => Promise<void>;
}

export interface LinkStatusState {
  loading: boolean;
}

export default class LinkStatus extends Component<ILinkStatusAttrs, LinkStatusState> {
  state = {
    loading: false,
  };


  view(): Mithril.Children {
    return (
      <div className={`LinkedAccountsList-item LinkedAccountsList-item--${this.attrs.provider.name()}`}>
        {this.iconView()}
        {this.statusView()}
        {this.actionView()}
      </div>
    );
  }

  iconView() {
    return (
      <div className="LinkedAccountsList-item-icon">
        <Icon name={this.attrs.provider.icon()} className={`Provider-Icon Provider-Icon--${this.attrs.provider.name()}`} />
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
      const returnTo = encodeURIComponent(window.location.pathname + window.location.search);
      const linkUrl = `${app.forum.attribute<string>('baseUrl')}/auth/${provider.name()}?linkTo=${user.id()}&returnTo=${returnTo}`;

      return (
        <div className="LinkedAccountsList-item-actions">
          <Button
            className={`Button FoFLogInButton LogInButton--${provider.name()}`}
            icon={provider.icon()}
            onclick={(e: MouseEvent) => {
              e.preventDefault();
              window.location.href = linkUrl;
            }}
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
      await this.attrs.refresh?.();
      this.state.loading = false;
    }
  }
}
