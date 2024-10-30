import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import FieldSet from 'flarum/common/components/FieldSet';
import listItems from 'flarum/common/helpers/listItems';
import ItemList from 'flarum/common/utils/ItemList';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';

import LinkedAccount from '../models/LinkedAccount';
import LinkStatus from './LinkStatus';

interface IAttrs {
  user: User;
}

interface IState {
  loadingAdditional: boolean;
  errorLoadingAdditional: boolean;
}

export default class LinkedAccounts extends Component<IAttrs, IState> {
  state: IState = {
    loadingAdditional: true,
    errorLoadingAdditional: false,
  };

  oncreate(vnode: Mithril.VnodeDOM<IAttrs, this>): void {
    super.oncreate(vnode);
    this.loadLinkedAccounts();
  }

  view(): Mithril.Children {
    const linkedAccounts = app.store.all<LinkedAccount>('linked-accounts');

    return (
      <FieldSet label={app.translator.trans('fof-oauth.forum.user.settings.linked-account.label')}>
        <p className="helpText">{app.translator.trans('fof-oauth.forum.user.settings.linked-account.help')}</p>

        {this.state.loadingAdditional ? (
          <LoadingIndicator containerClassName="LinkedAccounts-Loading" />
        ) : (
          <ul className="LinkedAccounts-List">{listItems(this.linkedAccountsItems(linkedAccounts, this.attrs.user).toArray())}</ul>
        )}
      </FieldSet>
    );
  }

  linkedAccountsItems(linkedAccounts: LinkedAccount[], user: User): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    linkedAccounts.forEach((linkedAccount) => {
      items.add(linkedAccount.name(), <LinkStatus provider={linkedAccount} user={user} />, linkedAccount.priority());
    });

    return items;
  }

  async loadLinkedAccounts() {
    await app.store.find<LinkedAccount[]>('users/' + this.attrs.user.id() + '/linked-accounts', {});
    this.state.loadingAdditional = false;
    m.redraw();
  }
}
