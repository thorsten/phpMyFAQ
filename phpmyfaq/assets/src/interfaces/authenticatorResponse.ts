interface ClientData {
  type: string;
  challenge: string;
  origin: string;
  crossOrigin?: boolean;
  tokenBinding?: {
    status: string;
    id?: string;
  };
}

export interface AuthenticatorResponse {
  type: string;
  originalChallenge: number[];
  rawId: number[];
  response: {
    authenticatorData: number[];
    clientData: ClientData;
    clientDataJSONarray: number[];
    signature: number[];
  };
}

export type Callback = (success: boolean, info: string | AuthenticatorResponse) => void;
