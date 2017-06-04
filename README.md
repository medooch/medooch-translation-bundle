# Medooch Translation Bundle

This bundle offer to developers the possibilities to generate all i18n files from the %locale_parameter% to %locales_parameters%.

#### Important:
To keep the original files as they are, we are working under the app / Resources directory. You must override any bundles you want to generate i18n files or correct spelling errors.

Configuration
----
    // app/config/config_dev.yml
    medooch_translation:
        i18n:
            bundles:
                - TestBundle
                - translations
                ....

Usage
----
1. French Spelling Correction
    
        bin/console medooch:i18n:spelling
    
2. I18n Generate files
    
        bin/console medooch:i18n:translations
