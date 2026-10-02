// node assets/admin/expandInvalidBlocks/patch.test.mjs
// The logic of patch.js with stand-ins for the admin classes (no admin, no browser needed).
import assert from 'node:assert/strict';
import {blockIndexesWithErrors, installExpandInvalidBlocks, shouldExpand} from './patch.js';

// --- which blocks have errors
assert.deepEqual(blockIndexesWithErrors([null, {title: {}}, undefined, {x: 1}], true), [1, 3]);
assert.deepEqual(blockIndexesWithErrors([null, {title: {}}], false), [], 'errors that are not shown do not open anything');
assert.deepEqual(blockIndexesWithErrors({}, true), [], 'a plain error object is no list of blocks');
assert.deepEqual(blockIndexesWithErrors(undefined, true), []);
assert.deepEqual(blockIndexesWithErrors([{}, null], true), [], 'an empty error object is no error');

// --- when to open
const error = [{a: 1}];
assert.equal(shouldExpand({error, showAllErrors: true}, {error, showAllErrors: true}), false, 'nothing changed');
assert.equal(shouldExpand({error, showAllErrors: true}, {error: [{a: 1}], showAllErrors: true}), true, 'a new check of the form');
assert.equal(shouldExpand({error, showAllErrors: false}, {error, showAllErrors: true}), true, 'the first save attempt');
assert.equal(shouldExpand({error, showAllErrors: true}, {error: [{a: 1}], showAllErrors: false}), false, 'errors are not shown');

// --- the installed hooks, with stand-ins (the lifecycle methods exist, like after mobx-react patched them)
class BlockCollection {
    constructor(props, count) {
        this.props = props;
        this.expandedBlocks = new Array(count).fill(false);
    }

    componentDidMount() {}

    handleExpand(index) {
        this.expandedBlocks[index] = true;
    }
}
class FieldBlocks {
    constructor(props) {
        this.props = props;
        this.renderBlockContent = () => null;
    }

    componentDidMount() {}

    componentDidUpdate() {}
}
const queue = [];
installExpandInvalidBlocks({FieldBlocks, BlockCollection, toPlain: (value) => value, defer: (callback) => queue.push(callback)});
const run = () => {
    while (queue.length) {
        queue.shift()();
    }
};

const field = new FieldBlocks({error: [null, {title: {}}, null], showAllErrors: true});
const list = new BlockCollection({renderBlockContent: field.renderBlockContent}, 3);

// the list mounts before its field (children first); the field opens the block with the error
list.componentDidMount();
field.componentDidMount();
run();
assert.deepEqual(list.expandedBlocks, [false, true, false], 'only the block with the error opens');

// the editor collapses it again and edits something (the errors stay the same object): it stays closed
list.expandedBlocks[1] = false;
field.componentDidUpdate({...field.props});
run();
assert.deepEqual(list.expandedBlocks, [false, false, false], 'same errors: nothing opens');

// the next failed save brings new errors: the block opens again, even though it was collapsed
field.props = {error: [null, {title: {}}, null], showAllErrors: true};
field.componentDidUpdate({error: [null, {title: {}}, null], showAllErrors: true});
run();
assert.deepEqual(list.expandedBlocks, [false, true, false]);

// no errors shown: a field that mounts later does not open anything
const quiet = new FieldBlocks({error: [{a: 1}], showAllErrors: false});
const quietList = new BlockCollection({renderBlockContent: quiet.renderBlockContent}, 1);
quietList.componentDidMount();
quiet.componentDidMount();
run();
assert.deepEqual(quietList.expandedBlocks, [false]);

// a field without a mounted list does not fail
const lonely = new FieldBlocks({error: [{a: 1}], showAllErrors: true});
lonely.componentDidMount();
run();

console.log('expandInvalidBlocks: all checks passed');
