var mysql = require('mysql');
var env = require('../environemnt');


/** RWPoolCtx Class **/
var _RWPoolCtx = function () {
    this._mysql_read_pools = [];
    this._mysql_write_pools = [];
}

_RWPoolCtx.prototype._getConnection = function (cb, mutable) {
    var pool = mutable? this._mysql_write_pools: this._mysql_read_pools;
    var poollen = pool.length;
    if (poollen > 0) {
        var poolidx = Math.floor(Math.random()*poollen);
        pool = pool[poolidx];
        if (pool && pool.getConnection !== undefined)
            pool.getConnection(cb);
    }
    return this;
}
_RWPoolCtx.prototype.getReadConnection = function (cb) {
    this._getConnection(cb, false);
    return this;
}
_RWPoolCtx.prototype.getWriteConnection = function (cb) {
    this._getConnection(cb, true);
    return this;
}
_RWPoolCtx.prototype.getConnection = function (cb) {
    return this.getWriteConnection(cb);
}
_RWPoolCtx.prototype._add = function (options, mutable) {
    var pool = mutable? this._mysql_write_pools: this._mysql_read_pools;
    pool.push(mysql.createPool(options));
    return this;
}

_RWPoolCtx.DefaultConnectionLimit = 32;
_RWPoolCtx.sharedInstance = null;
_RWPoolCtx.configureDefault = function (read, write) {
    var instance = this.sharedInstance;
    if (instance === null) {
        instance = this.sharedInstance = new _RWPoolCtx();
        if (read && read.length !== undefined && read.length > 0) {
            for (var i in read)
                instance._add(read[i], false);
        }
        if (write && write.length !== undefined && write.length > 0) {
            for (var i in write)
                instance._add(write[i], true);
        }
    }
    return instance;
}
_RWPoolCtx.getSharedInstance = function () {
    return this.sharedInstance;
}



/** Exports **/
module.exports = {};
for (var k in mysql)
    module.exports[k] = mysql[k];
module.exports.makeConfiguration = function (server, username, password, database, port) {
    var limit = env.get('MySQLConnectionLimitKey');
    limit = limit === null? _RWPoolCtx.DefaultConnectionLimit : limit;
    return {
        host : server,
        user : username,
        password : password,
        database : database,
        port : port,
        insecureAuth : true,
        connectionLimit : _RWPoolCtx.DefaultConnectionLimit,
    };
}
module.exports.configureDefault = function (read, write) {
    return _RWPoolCtx.configureDefault(read, write);
}
module.exports.getReadConnection = function (cb) {
    var instance = _RWPoolCtx.getSharedInstance();
    return instance === null? null: instance.getReadConnection(cb);
}
module.exports.getWriteConnection = function  (cb) {
    var instance = _RWPoolCtx.getSharedInstance();
    return instance === null? null: instance.getWriteConnection(cb);
}
module.exports.getConnection = function (cb) {
    var instance = _RWPoolCtx.getSharedInstance();
    return instance === null? null: instance.getConnection(cb);
}
