import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type LinkedAccount from '../models/LinkedAccount';
import type Mithril from 'mithril';
import humanTime from 'flarum/common/helpers/humanTime';

interface IProviderInfoAttrs {
  provider: LinkedAccount;
}

export default class ProviderInfo extends Component<IProviderInfoAttrs> {
  view(vnode: Mithril.Vnode<IProviderInfoAttrs, this>): Mithril.Children {
    const { provider } = this.attrs;

    if (provider.orphaned()) {
      return (
        <div>
          <p className="LinkedAccountsList-item-title">{provider.name()}</p>
          <p className="helpText">{app.translator.trans('fof-oauth.forum.user.settings.linked-account.orphaned-account')}</p>
          {this.renderDates(provider)}
        </div>
      );
    }

    if (provider.linked()) {
      return (
        <div>
          <p className="LinkedAccountsList-item-title">{app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`)}</p>
          {this.renderDates(provider)}
        </div>
      );
    }

    return (
      <div>
        <p className="LinkedAccountsList-item-title">{app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`)}</p>
      </div>
    );
  }

  /**
   * Render the created and last used dates for a provider.
   */
  renderDates(provider: LinkedAccount): Mithril.Children {
    return (
      <dl>
        <dt className="LinkedAccountsList-item-title">{app.translator.trans('fof-oauth.forum.user.settings.linked-account.link-created-label')}</dt>
        <dd className="LinkedAccountsList-item-value">{humanTime(provider.firstLogin())}</dd>

        <dt className="LinkedAccountsList-item-title">{app.translator.trans('fof-oauth.forum.user.settings.linked-account.last-used-label')}</dt>
        <dd className="LinkedAccountsList-item-value">{humanTime(provider.lastLogin())}</dd>
      </dl>
    );
  }
}
