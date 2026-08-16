export interface Faq {
  id: number;
  language: string;
  solution_id: number;
  status: 'draft' | 'review' | 'published';
  sticky: string;
  category_id: string;
  question: string;
  updated: string; // Format: YYYYMMDDHHMMSS
  visits: number;
  created: string; // Format: YYYY-MM-DD HH:MM:SS
  isAllowedToPublish: boolean;
}

export interface FaqList {
  faqs: Faq[];
  isAllowedToTranslate: boolean;
}
