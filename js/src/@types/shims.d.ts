import 'flarum/forum/ForumApplication';
import 'flarum/common/models/User';

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    fof_oauth_linkingInProgress: boolean | undefined;
    fof_oauth_linkingProvider: string | undefined;
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    loginProvider(): string | null;
  }
}