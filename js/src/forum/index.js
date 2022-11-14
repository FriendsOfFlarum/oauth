import app from 'flarum/forum/app';
import addLinkedAccountsToUserSettings from './extend/addLinkedAccountsToUserSettings';
import extendLoginSignup from './extend/extendLoginSignup';
import LinkedAccount from './models/LinkedAccount';

app.initializers.add('fof/oauth', () => {
  app.store.models['linked-accounts'] = LinkedAccount;

  extendLoginSignup();
  addLinkedAccountsToUserSettings();
});
