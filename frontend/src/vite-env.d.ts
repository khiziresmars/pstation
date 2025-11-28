/// <reference types="vite/client" />

declare module '*.module.css' {
  const classes: { readonly [key: string]: string };
  export default classes;
}

interface ImportMetaEnv {
  readonly VITE_API_URL: string;
  readonly VITE_APP_NAME: string;
  readonly VITE_TELEGRAM_BOT_USERNAME: string;
  readonly VITE_ENABLE_PROMPTPAY: string;
  readonly VITE_ENABLE_YOOKASSA: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
