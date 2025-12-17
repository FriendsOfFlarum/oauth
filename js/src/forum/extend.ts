import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import LinkedAccount from './models/LinkedAccount';
import SsoGambit from './query/SsoGambit';

export default [
  new Extend.Model(User) //
    .attribute<string>('loginProvider'),

  new Extend.Store() //
    .add('linked-accounts', LinkedAccount),

  new Extend.Search() //
    .gambit('users', SsoGambit),
];
