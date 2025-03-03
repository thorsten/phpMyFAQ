interface FileItem {
  file: string;
  size: string;
  isImage: boolean;
  thumb: string;
  changed: string;
}

interface Source {
  baseurl: string;
  path: string;
  files: FileItem[];
  name: string;
}

interface Data {
  sources: Source[];
  code: number;
}
export interface MediaBrowserApiResponse {
  success: boolean;
  time: string;
  data: Data;
}
