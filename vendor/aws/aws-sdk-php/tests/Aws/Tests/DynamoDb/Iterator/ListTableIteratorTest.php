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

namespace Aws\Tests\DynamoDb\Model;

use Aws\DynamoDb\Iterator\ListTablesIterator;

/**
 * @covers Aws\DynamoDb\Iterator\ListTablesIterator
 */
class ListTableIteratorTest extends \Guzzle\Tests\GuzzleTestCase
{
    public function testIteratesListTableCommand()
    {
        $client = $this->getServiceBuilder()->get('dynamodb');
        $mock = $this->setMockResponse($client, array(
            'dynamodb/list_tables_has_more',
            'dynamodb/list_tables_final'
        ));

        $iterator = new ListTablesIterator($client->getCommand('ListTables'));

        $this->assertEquals(array('Table1', 'Table2', 'Table3', 'Table4', 'Table5'), $iterator->toArray());

        $requests = $mock->getReceivedRequests();
        $this->assertEquals(2, count($requests));
        $json = json_decode((string) $requests[1]->getBody(), true);
        $this->assertEquals('Table3', $json['ExclusiveStartTableName']);
    }
}
