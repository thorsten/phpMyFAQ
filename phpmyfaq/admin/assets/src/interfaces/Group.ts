export interface Group {
  group_id: string;
  name: string;
  description?: string;
  auto_join?: string;
}

export interface CategoryItem {
  id: number;
  name: string;
  parent_id: number;
  level?: number;
}

export interface CategoryRestrictions {
  [rightId: string]: number[];
}

export interface LanguageItem {
  code: string;
  label: string;
}

export interface LanguageRestrictions {
  [rightId: string]: string[];
}
