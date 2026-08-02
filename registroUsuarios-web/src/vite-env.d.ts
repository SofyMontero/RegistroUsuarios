/// <reference types="vite/client" />

declare namespace WebSdk {
  interface WebChannelOptions {
    debug?: boolean;
  }
}

declare const WebSdk: {
  __isStub?: boolean;
  WebChannel: new (options?: WebSdk.WebChannelOptions) => unknown;
  WebChannelClient: new (channelName?: string, options?: unknown) => unknown;
};
