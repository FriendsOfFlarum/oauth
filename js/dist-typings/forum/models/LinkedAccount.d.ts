import Model from 'flarum/common/Model';
export default class LinkedAccount extends Model {
    name(): string;
    icon(): string;
    priority(): number;
    linked(): boolean;
    orphaned(): boolean;
    identifier(): string;
    providerIdentifier(): string;
    firstLogin(): Date;
    lastLogin(): Date;
}
