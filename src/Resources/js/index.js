import {toJS} from 'mobx';
import BlockCollection from 'sulu-admin-bundle/components/BlockCollection';
import {FieldBlocks} from 'sulu-admin-bundle/containers';
import {installExpandInvalidBlocks} from './expandInvalidBlocks/patch.js';

// After a failed save, blocks that contain an invalid field open up, so the red marks are visible (and only those blocks).
installExpandInvalidBlocks({FieldBlocks, BlockCollection, toPlain: toJS});
