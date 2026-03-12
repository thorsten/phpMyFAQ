# 9. AI-Assisted Translation Feature

phpMyFAQ includes an AI-assisted translation feature that helps you translate your FAQ content, custom pages, 
categories, and news articles into multiple languages using professional translation services.

## 9.1 Overview

The translation feature integrates with leading translation APIs to provide high-quality, automated translations while 
preserving HTML formatting in your content. This saves time when creating multilingual content and ensures consistency 
across different language versions of your FAQ.

## 9.2 Supported Translation Providers

phpMyFAQ supports five translation providers:

1. **Google Cloud Translation** - Google's neural machine translation service
2. **DeepL** – Known for high-quality, natural-sounding translations
3. **Azure Translator** – Microsoft's translation service
4. **Amazon Translate** - AWS translation service
5. **LibreTranslate** - Open-source, self-hosted option

Each provider has different pricing models, language support, and quality characteristics. Choose the one that best 
fits your needs and budget.

## 9.3 Configuration

### 9.3.1 Accessing Translation Settings

1. Log in to the phpMyFAQ admin panel
2. Navigate to **Configuration** → **Translation** tab
3. Select your preferred translation provider from the dropdown
4. Enter the required API credentials
5. Click **Save Configuration**

### 9.3.2 Provider-Specific Setup

#### 9.3.2.1 Google Cloud Translation

**Prerequisites:**
- A Google Cloud Platform account
- Billing enabled on your GCP project
- Cloud Translation API enabled

**Setup Steps:**

1. Go to the [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project or select an existing one
3. Navigate to **APIs & Services** → **Library**
4. Search for "Cloud Translation API" and enable it
5. Go to **APIs & Services** → **Credentials**
6. Click **Create Credentials** → **API Key**
7. Copy the API key
8. In phpMyFAQ admin, set:
   - **Provider**: Google Cloud Translation
   - **Google Cloud Translation API key**: Paste your API key

**Pricing:** Pay-as-you-go, typically $20 per million characters
**Languages:** 100+ languages supported

#### 9.3.2.2 DeepL

**Prerequisites:**
- A DeepL account (Free or Pro)
- DeepL API key

**Setup Steps:**

1. Sign up at [DeepL API](https://www.deepl.com/pro-api)
2. Choose between Free or Pro plan:
   - **Free**: Up to 500,000 characters/month
   - **Pro**: Pay-as-you-go, higher limits
3. Get your API key from the account settings
4. In phpMyFAQ admin, set:
   - **Provider**: DeepL
   - **DeepL API key**: Paste your API key
   - **Use DeepL Free API**: Check if using Free plan, uncheck for Pro

**Pricing:**
- Free: €0 for up to 500,000 characters/month
- Pro: ~€20 per million characters

**Languages:** 30+ languages (fewer than Google, but higher quality)

#### 9.3.2.3 Azure Translator

**Prerequisites:**
- Microsoft Azure account
- Azure Translator resource created

**Setup Steps:**

1. Sign in to the [Azure Portal](https://portal.azure.com)
2. Click **Create a resource** → Search for "Translator"
3. Click **Create** and configure:
   - Select your subscription and resource group
   - Choose a region (e.g., East US, West Europe)
   - Select a pricing tier (F0 for free tier)
4. After deployment, go to your Translator resource
5. Navigate to **Keys and Endpoint**
6. Copy **Key 1** and note the **Region**
7. In phpMyFAQ admin, set:
   - **Provider**: Azure Translator
   - **Azure Translator API key**: Paste Key 1
   - **Azure region**: Enter your region (e.g., eastus, westeurope)

**Pricing:**
- Free tier: 2 million characters/month
- Standard: ~$10 per million characters

**Languages:** 90+ languages supported

#### 9.3.2.4 Amazon Translate

**Prerequisites:**
- AWS account
- IAM user with Amazon Translate permissions

**Setup Steps:**

1. Sign in to the [AWS Console](https://console.aws.amazon.com)
2. Navigate to **IAM** → **Users**
3. Create a new user or select an existing one
4. Attach the policy **TranslateFullAccess** (or create a custom policy with `translate:TranslateText` permission)
5. Go to **the Security credentials** tab
6. Click **Create access key**
7. Choose **Third-party service** and create the key
8. Copy the **Access Key ID** and **Secret Access Key**
9. In phpMyFAQ admin, set:
   - **Provider**: Amazon Translate
   - **Amazon Translate AWS Access Key ID**: Paste access key ID
   - **Amazon Translate AWS Secret Access Key**: Paste secret key
   - **Amazon Translate AWS region**: Enter region (e.g., us-east-1, eu-west-1)

**Pricing:** Pay-as-you-go, typically $15 per million characters
**Languages:** 75+ languages supported

#### 9.3.2.5 LibreTranslate (Self-Hosted)

**Prerequisites:**
- A server to host LibreTranslate (optional – can use public instance)
- Docker or Python environment

**Setup Steps:**

**Option 1: Use Public Instance**
1. In phpMyFAQ admin, set:
   - **Provider**: LibreTranslate
   - **LibreTranslate server URL**: `https://libretranslate.com`
   - **LibreTranslate API key**: Leave empty (or get a free API key from libretranslate.com)

**Option 2: Self-Host**
1. Install LibreTranslate on your server:
   ```bash
   # Using Docker
   docker run -d -p 5000:5000 libretranslate/libretranslate

   # Or using Python
   pip install libretranslate
   libretranslate
   ```
2. In phpMyFAQ admin, set:
   - **Provider**: LibreTranslate
   - **LibreTranslate server URL**: Your server URL (e.g., `http://your-server:5000`)
   - **LibreTranslate API key**: Leave empty or set if you configured API key protection

**Pricing:** Free if self-hosted, or usage limits on public instance
**Languages:** 30+ languages supported

## 9.4 Using the Translation Feature

### 9.4.1 Translating FAQs

1. Navigate to **FAQs** → Edit an existing FAQ
2. In the FAQ editor, switch to the **Translation** tab (or click **Translate FAQ**)
3. Select the **target language** from the dropdown
4. The original language will be auto-detected
5. Click the **"Translate with AI"** button
6. The system will automatically translate:
   - Question text
   - Answer (preserving HTML formatting like bold, links, lists)
   - Keywords
7. Review the translated content
8. Make any necessary edits or adjustments
9. Click **Save** to save the translation

**Important:** HTML tags in the answer (like `<p>`, `<strong>`, `<a>`, etc.) are automatically preserved during 
translation.

### 9.4.2 Translating Custom Pages

1. Navigate to **Content** → **Custom Pages**
2. Select a page and click **Translate**
3. Select the **target language**
4. Click **"Translate with AI"** button
5. The system will translate:
   - Page title
   - Page content (HTML preserved)
   - SEO title (meta title)
   - SEO description (meta description)
6. Review and edit the translations
7. Configure the language and other settings in the **Settings** tab
8. Click **Save** to add the translation

### 9.4.3 Translating Categories

1. Navigate to **Categorize**
2. Select a category and click **Translate Category**
3. Select the **target language**
4. Click **"Translate with AI"** button
5. The system will translate:
   - Category name
   - Category description
6. Review the translations
7. Click **Save** to add the translation

### 9.4.4 Translating News Articles

Currently, news articles use a similar workflow:
1. Create or edit a news article
2. Use the translation interface to create language versions
3. The AI can assist with translating the headline and content

## 9.5 Best Practices

### 9.5.1 Review All Translations

AI translation is very good, but not perfect. Always review and edit translations before publishing, especially for:
- Technical terms specific to your domain
- Brand names and product names
- Legal or compliance-related content
- Idiomatic expressions

### 9.5.2 Maintain Consistency

- Use the same translation provider across your site for consistency
- Create a glossary of key terms and their preferred translations
- Use the same terminology in source content to get consistent translations

### 9.5.3 HTML Formatting

- The AI preserves HTML tags, but complex nested structures may occasionally need adjustment
- Always preview translated content to ensure formatting is correct
- Simple formatting (bold, italic, links, lists) works best

### 9.5.4 Language Selection

Consider language support when choosing a provider:
- **Most languages**: Google Cloud Translation (100+)
- **European languages (highest quality)**: DeepL
- **Enterprise requirements**: Azure Translator or Amazon Translate
- **Privacy/self-hosted**: LibreTranslate

### 9.5.5 Cost Management

Monitor your translation usage to control costs:
- Start with free tiers when available (Azure, DeepL Free, LibreTranslate)
- Translate in batches to stay organized
- Review auto-translated content rather than re-translating
- Consider caching translations for frequently updated content

## 9.6 Workflow Recommendations

### 9.6.1 Initial Setup
1. Choose your translation provider based on languages needed and budget
2. Configure API credentials in phpMyFAQ admin
3. Test with a single FAQ to verify the integration works
4. Review quality and adjust provider if needed

### 9.6.2 Creating Multilingual Content
1. **Write in your primary language first** – Create high-quality source content
2. **Translate one language at a time** – Easier to review and maintain consistency
3. **Use AI as first draft** – Let AI do the initial translation
4. **Review and refine** – Edit translations for accuracy and tone
5. **Keep active** – Set translations to active/inactive to control publishing

### 9.6.3 Updating Content
1. Update the primary language version first
2. Mark translations that need updating
3. Re-translate or manually update other languages
4. Review changes before publishing

## 9.7 Troubleshooting

### 9.7.1 Translation button is disabled
- **Check provider configuration**: Ensure you've selected a provider and entered valid credentials
- **Check language selection**: Source and target languages must be different
- **Check language support**: Ensure both languages are supported by your chosen provider

### 9.7.2 Translation fails or returns errors
- **API credentials**: Verify your API key/credentials are correct and active
- **API quota**: Check if you've exceeded your free tier or quota limits
- **Network issues**: Ensure your server can reach the translation API
- **Content length**: Very long content may need to be translated in smaller chunks

### 9.7.3 Poor translation quality
- **Try a different provider**: DeepL often has better quality for European languages
- **Review source content**: Complex or unclear source content leads to poor translations
- **Check language support**: Some language pairs work better than others
- **Edit after translation**: Use AI as a starting point, then refine manually

### 9.7.4 HTML formatting issues
- **Check source HTML**: Ensure source content has valid, clean HTML
- **Simplify complex structures**: Very complex HTML may need manual adjustment
- **Preview before saving**: Always preview translated content
- **Re-translate if needed**: If formatting is broken, try translating again

### 9.7.5 Cost concerns
- **Monitor usage**: Check your provider's dashboard for usage statistics
- **Use free tiers**: Start with free options (Azure, DeepL Free, LibreTranslate)
- **Set up billing alerts**: Configure alerts in your cloud provider console
- **Optimize workflow**: Translate only when needed, not repeatedly

## 9.8 Security and Privacy

### 9.8.1 Data Handling
- Translation content is sent to third-party APIs (except LibreTranslate self-hosted)
- Consider data privacy regulations (GDPR, etc.) when choosing a provider
- For sensitive content, consider self-hosted LibreTranslate
- API credentials are stored encrypted in the database

### 9.8.2 API Security
- Keep API keys secure and never share them
- Use environment-specific keys (dev vs. production)
- Rotate keys periodically
- Monitor API usage for anomalies
- Consider IP restrictions where available

## 9.9 Supported Languages by Provider

### Google Cloud Translation
100+ languages including Arabic, Chinese (Simplified & Traditional), Czech, Danish, Dutch, English, Finnish, French, 
German, Greek, Hebrew, Hindi, Hungarian, Indonesian, Italian, Japanese, Korean, Norwegian, Polish, Portuguese, Romanian, 
Russian, Spanish, Swedish, Thai, Turkish, Ukrainian, Vietnamese, and many more.

### DeepL
30+ languages including Bulgarian, Chinese (Simplified), Czech, Danish, Dutch, English (US & UK), Estonian, Finnish, 
French, German, Greek, Hungarian, Indonesian, Italian, Japanese, Korean, Latvian, Lithuanian, Norwegian, Polish, 
Portuguese (Brazilian & European), Romanian, Russian, Slovak, Slovenian, Spanish, Swedish, Turkish, Ukrainian.

### Azure Translator
90+ languages including all major world languages and many regional variants.

### Amazon Translate
75+ languages including all major world languages and many Asian, Middle Eastern, and African languages.

### LibreTranslate
30+ languages including Arabic, Chinese, Czech, Danish, Dutch, English, Finnish, French, German, Greek, Hebrew, Hindi, 
Hungarian, Indonesian, Italian, Japanese, Korean, Polish, Portuguese, Russian, Spanish, Swedish, Turkish, Ukrainian.

## Support and Resources

### Provider Documentation
- [Google Cloud Translation](https://cloud.google.com/translate/docs)
- [DeepL API](https://www.deepl.com/docs-api)
- [Azure Translator](https://docs.microsoft.com/azure/cognitive-services/translator/)
- [Amazon Translate](https://docs.aws.amazon.com/translate/)
- [LibreTranslate](https://libretranslate.com/)

### Getting Help
- phpMyFAQ Documentation: https://www.phpmyfaq.de/docs
- phpMyFAQ Forum: https://forum.phpmyfaq.de
- GitHub Issues: https://github.com/thorsten/phpMyFAQ/issues

### Contributing
If you find issues with the translation feature or have suggestions for improvements, please report them on our GitHub 
repository.
