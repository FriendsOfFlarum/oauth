import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserSecurityPage from 'flarum/forum/components/UserSecurityPage';
import ItemList from 'flarum/common/utils/ItemList';
import LinkedAccounts from '../components/LinkedAccounts';
import type Mithril from 'mithril';

export default function addLinkedAccountsToUserSecurityPage() {
  extend(UserSecurityPage.prototype, 'settingsItems', function (items: ItemList<Mithril.Children>) {
    if (this.user !== app.session.user) {
      return;
    }

    items.add('linkedAccounts', <LinkedAccounts user={this.user} />, 5);
  });
}
