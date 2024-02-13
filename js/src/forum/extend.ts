import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import LinkedAccount from './models/LinkedAccount';
import Forum from 'flarum/common/models/Forum';

export default [
  new Extend.Model(User) //
    .attribute<string>('loginProvider'),

  new Extend.Model(Forum) //
    .attribute<boolean>('fofOauthModerate'),

  new Extend.Store() //
    .add('linked-accounts', LinkedAccount),
];
