<?php
/**
 * Copyright 2010-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\DynamoDb\Iterator;
use Guzzle\Service\Resource\Model;

use Aws\Common\Iterator\AbstractResourceIterator;

/**
 * Iterate over a ListTables command
 */
class ListTablesIterator extends AbstractResourceIterator
{
    /**
     * {@inheritdoc}
     */
    protected function applyNextToken()
    {
        $this->command->set('ExclusiveStartTableName', $this->nextToken);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleResults(Model $result)
    {
        return $result['TableNames'];
    }

    /**
     * {@inheritdoc}
     */
    protected function determineNextToken(Model $result)
    {
        $this->nextToken = isset($result['LastEvaluatedTableName']) ? $result['LastEvaluatedTableName'] : false;
    }
}
