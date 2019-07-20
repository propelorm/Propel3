<?php

namespace Propel\Runtime;

class Events
{
    const PRE_COMMIT = 'propel.pre_commit';
    const COMMIT = 'propel.commit';

    const PRE_PERSIST = 'propel.pre_persist';
    const PERSIST = 'propel.persist';

    const PRE_SAVE = 'propel.pre_save';
    const SAVE = 'propel.save';

    const PRE_UPDATE = 'propel.pre_update';
    const UPDATE = 'propel.update';

    const PRE_INSERT = 'propel.pre_insert';
    const INSERT = 'propel.insert';

    const PRE_DELETE = 'propel.pre_delete';
    const DELETE = 'propel.delete';
}
