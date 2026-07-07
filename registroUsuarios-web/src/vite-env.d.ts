/// <reference types="vite/client" />

declare namespace WebSdk {
  interface WebChannelOptions {
    debug?: boolean;
  }
}

declare const WebSdk: {
  WebChannel: new (options?: WebSdk.WebChannelOptions) => unknown;
};
