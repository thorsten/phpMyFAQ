export interface PopularSearch {
  id: number;
  searchterm: string;
  number: number | string;
}

export type PopularSearchResponse = PopularSearch[];
