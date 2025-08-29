import 'flarum/forum/ForumApplication';
import 'flarum/common/models/User';

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    fof_oauth_linkingInProgress?: boolean;
    fof_oauth_linkingProvider?: string;
    fof_oauth_loginInProgress?: boolean;
    linkingComplete: () => Promise<void>;
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    loginProvider(): string | null;
  }
}
