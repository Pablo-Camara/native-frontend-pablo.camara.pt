import json

with open('assets/json/translations/translations.json') as json_file:
    data = json.load(json_file)
    languageData = {}
    for translationStringId in data:
        for translationLanguageCode in data[translationStringId]:
            if translationLanguageCode not in languageData:
                languageData[translationLanguageCode] = {}

            languageData[translationLanguageCode][translationStringId] = data[translationStringId][translationLanguageCode];
    
    for languageCode in languageData:
        with open('public/assets/json/translations_' + languageCode + '.json', 'w') as outfile:
                json.dump(languageData[languageCode], outfile)
    