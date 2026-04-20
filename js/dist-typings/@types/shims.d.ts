import 'flarum/forum/ForumApplication';
import 'flarum/admin/AdminApplication';
import 'flarum/common/models/User';

export interface OAuthAdminProvider {
  name: string;
  icon: string;
  link: string;
  fields: Record<string, string>;
}

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    fof_oauth_linkingInProgress?: boolean;
    fof_oauth_linkingProvider?: string;
    fof_oauth_loginInProgress?: boolean;
    linkingComplete: () => Promise<void>;
  }
}

declare module 'flarum/admin/AdminApplication' {
  interface AdminApplicationData {
    'fof-oauth': OAuthAdminProvider[];
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    loginProvider(): string | null;
  }
}
