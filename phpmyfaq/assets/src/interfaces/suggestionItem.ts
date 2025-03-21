import { AutocompleteItem } from 'autocompleter';

export interface SuggestionItem {
  question: string;
  category: string;
  url: string;
}

export type Suggestion = SuggestionItem & AutocompleteItem;
