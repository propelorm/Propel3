Propel 3 ToDo list

- PHP 7.4+ (move to php 8 when annotations will be ready)
- Use twig instead of mustache, to move some render logic into the templates. 
- Refactor model criteria
- Refactor entity map
- Refactor platform: split classes into smaller pieces. It can be done via twig templates.
- EnityDiff: use FieldDiff objects instead of arrays whenever it's possible (renamed fields)
- Refactor the XmlDumper and create others for the other supported formats (yaml, json, php)
- Use phootwork/lang library to manipulate arrays and strings (still introduced).
- Use phootwork/lang library for `toArray` method.
- Use phootwork/collection to refactor Collections
- Use phootwork/file for file and directory manipulation (it's stream compatible!!)
- Move `Domain::getPhpDefaultValue` and `Field::getPhpDefaultValue` in `FieldDefaultValue` class or create a new class to 
represent default values 
- Inspect code to remove old style array
- Remove deprecated methods
- Implement `Propel\Generator\Builder\Om\Component\ActiveRecordTrait\GenericMutatorMethods` if needed
- Port `VersionableBehavior` from Propel2 to Propel3
- Fix Mysql tests
- Fix Postgres tests
- Fix sqlite tests
- When test suite'll be green, remove commented code
- Introduce `vimeo/psalm` static analisys tool
- Remove `Propel\Generator\Builder\Sql` namespace ?
- Remove `Propel\Generator\Builder\Util\DataRow`
- Move from Travis to Github Actions and test everything against windows and MacOsx, too
- Refactor Propel and test suite for massively usage of *vfsStream* instead of filesystem
- Try to remove Quickbuilder and use vfsStream Files  instead
Basically we should remove unsupported functions (i.e. chdir, getcwd, pathinfo etc.)
- Write the documentation and choose the tool. Maybe [MkDocs](https://www.mkdocs.org/)?
