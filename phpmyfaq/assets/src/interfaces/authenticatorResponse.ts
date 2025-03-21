export interface AuthenticatorResponse {
  type: string;
  originalChallenge: number[];
  rawId: number[];
  response: {
    authenticatorData: number[];
    clientData: any;
    clientDataJSONarray: number[];
    signature: number[];
  };
}

export type Callback = (success: boolean, info: string | AuthenticatorResponse) => void;
