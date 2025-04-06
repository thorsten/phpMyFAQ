interface Faq {
  id: number;
  language: string;
  solution_id: number;
  active: string;
  sticky: string;
  category_id: string;
  question: string;
  updated: string; // Format: YYYYMMDDHHMMSS
  visits: number;
  created: string; // Format: YYYY-MM-DD HH:MM:SS
}

export interface FaqResponse {
  faqs: Faq[];
}
