import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import LinkedAccount from './models/LinkedAccount';

export default [
  new Extend.Model(User) //
    .attribute<string>('loginProvider'),

  new Extend.Store() //
    .add('linked-accounts', LinkedAccount),
];
