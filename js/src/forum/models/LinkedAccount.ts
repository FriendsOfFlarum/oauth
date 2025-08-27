import Model from 'flarum/common/Model';

export default class LinkedAccount extends Model {
  name() {
    return Model.attribute<string>('name').call(this);
  }

  icon() {
    return Model.attribute<string>('icon').call(this);
  }

  priority() {
    return Model.attribute<number>('priority').call(this);
  }

  linked() {
    return Model.attribute<boolean>('linked').call(this);
  }

  orphaned() {
    return Model.attribute<boolean>('orphaned').call(this);
  }

  identifier() {
    return Model.attribute<string>('identifier').call(this);
  }

  providerIdentifier() {
    return Model.attribute<string>('providerIdentifier').call(this);
  }

  firstLogin() {
    return Model.attribute<Date, string>('firstLogin', Model.transformDate).call(this);
  }

  lastLogin() {
    return Model.attribute<Date, string>('lastLogin', Model.transformDate).call(this);
  }
}
