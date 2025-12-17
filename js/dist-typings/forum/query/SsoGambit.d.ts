import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class SsoGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
    enabled(): boolean;
}
