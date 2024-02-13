import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type LinkedAccount from '../models/LinkedAccount';
import type Mithril from 'mithril';
import humanTime from 'flarum/common/helpers/humanTime';
import LabelValue from 'flarum/common/components/LabelValue';
import ItemList from 'flarum/common/utils/ItemList';

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
          <div className="LinkedAccountsList">{this.providerInfoItems(provider).toArray()}</div>
        </div>
      );
    }

    if (provider.linked()) {
      return (
        <div>
          <p className="LinkedAccountsList-item-title">{app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`)}</p>
          <div className="LinkedAccountsList">{this.providerInfoItems(provider).toArray()}</div>
        </div>
      );
    }

    return (
      <div>
        <p className="LinkedAccountsList-item-title">{app.translator.trans(`fof-oauth.forum.providers.${provider.name()}`)}</p>
      </div>
    );
  }

  providerInfoItems(provider: LinkedAccount): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'firstLogin',
      <LabelValue
        label={app.translator.trans('fof-oauth.forum.user.settings.linked-account.link-created-label')}
        value={humanTime(provider.firstLogin())}
      />,
      100
    );

    items.add(
      'lastLogin',
      <LabelValue
        label={app.translator.trans('fof-oauth.forum.user.settings.linked-account.last-used-label')}
        value={humanTime(provider.lastLogin())}
      />,
      90
    );

    return items;
  }
}
