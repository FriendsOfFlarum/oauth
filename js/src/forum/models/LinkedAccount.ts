import Model from 'flarum/common/Model';

export default class LinkedAccount extends Model {
  name = Model.attribute<string>('name');
  icon = Model.attribute<string>('icon');
  priority = Model.attribute<number>('priority');
  linked = Model.attribute<boolean>('linked');
  orphaned = Model.attribute<boolean>('orphaned');
  identifier = Model.attribute<string>('identifier');
  providerIdentifier = Model.attribute<string>('providerIdentifier');
  firstLogin = Model.attribute('firstLogin', Model.transformDate);
  lastLogin = Model.attribute('lastLogin', Model.transformDate);
}
