import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';

import LinkedAccount from '../models/LinkedAccount';
import LinkStatus from './LinkStatus';
import ItemList from 'flarum/common/utils/ItemList';

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

  view(vnode: Mithril.Vnode<IAttrs, this>) {
    const user = this.attrs.user;

    const linkedAccounts = app.store.all<LinkedAccount>('linked-accounts');

    return (
      <div className="LinkedAccounts">
        <fieldset>
          <legend>{app.translator.trans('fof-oauth.forum.user.settings.linked-account.label')}</legend>
          <p className="helpText">{app.translator.trans('fof-oauth.forum.user.settings.linked-account.help')}</p>

          {this.state.loadingAdditional && <LoadingIndicator containerClassName="LinkedAccounts-Loading" />}
          {this.state.errorLoadingAdditional && (
            <p className="LinkedAccounts-LinkedStatus" role="status">
              {app.translator.trans('fof-oauth.forum.user.settings.error_loading_additional')}
            </p>
          )}

          {linkedAccounts.length > 0 && <ul className="LinkedAccounts-List">{this.linkedAccountsItems(linkedAccounts, user).toArray()}</ul>}
        </fieldset>
      </div>
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
    await app.store.find<LinkedAccount[]>('linked-accounts', {});
    this.state.loadingAdditional = false;
    m.redraw();
  }
}
