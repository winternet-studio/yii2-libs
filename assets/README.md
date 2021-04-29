Each AssetBundle must have their own folder since they Yii works on paths and not on specific files in the AssetManager->publish() method.
If using shared folder eg. forceCopy option would be ineffective is another AssetBundle had already copied assets from the folder.
