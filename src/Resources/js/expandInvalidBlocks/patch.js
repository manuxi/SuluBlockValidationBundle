// After a failed save the admin shows the red marks of the invalid fields, but a block that is collapsed hides them: the
// editor has to open block after block to find the field. This opens the blocks that contain an error, and only those, one
// level after the other (a nested block list mounts when its block opens and opens its own blocks with errors).
//
// The classes are handed in (see app.js), so the logic can be tested without the admin.

/**
 * @param {unknown} errors plain errors of a block list: one entry per block, empty (null) when the block is fine
 * @param {boolean} showAllErrors the form shows its errors (the editor tried to save)
 *
 * @return {number[]} indexes of the blocks that have an error
 */
export const blockIndexesWithErrors = (errors, showAllErrors) => {
    if (!showAllErrors || !Array.isArray(errors)) {
        return [];
    }

    const indexes = [];
    errors.forEach((blockError, index) => {
        if (blockError && Object.keys(blockError).length > 0) {
            indexes.push(index);
        }
    });

    return indexes;
};

/**
 * Validation replaces the errors on every save attempt (and when the form is loaded), so a changed "error" means a new check
 * of the form; a block the editor collapsed again is opened again by the next failed save.
 */
export const shouldExpand = (prevProps, props) => (
    !!props.showAllErrors && (prevProps.error !== props.error || !prevProps.showAllErrors)
);

/**
 * @param {{FieldBlocks: Function, BlockCollection: Function, toPlain: Function, defer?: Function}} deps
 */
export const installExpandInvalidBlocks = ({FieldBlocks, BlockCollection, toPlain, defer = (callback) => setTimeout(callback, 0)}) => {
    // The list of blocks (BlockCollection) holds which block is open. It gets the render function of its field as a prop,
    // which is one function per field, so the field finds its list by that function.
    const collections = new WeakMap();

    const collectionDidMount = BlockCollection.prototype.componentDidMount;
    BlockCollection.prototype.componentDidMount = function (...args) {
        const result = collectionDidMount ? collectionDidMount.apply(this, args) : undefined;
        collections.set(this.props.renderBlockContent, this);

        return result;
    };

    const expand = (field) => {
        const collection = collections.get(field.renderBlockContent);
        if (!collection) {
            return;
        }

        blockIndexesWithErrors(toPlain(field.props.error), field.props.showAllErrors).forEach((index) => {
            if (index < collection.expandedBlocks.length && !collection.expandedBlocks[index]) {
                collection.handleExpand(index);
            }
        });
    };

    const fieldDidMount = FieldBlocks.prototype.componentDidMount;
    FieldBlocks.prototype.componentDidMount = function (...args) {
        const result = fieldDidMount ? fieldDidMount.apply(this, args) : undefined;
        if (this.props.showAllErrors) {
            defer(() => expand(this));
        }

        return result;
    };

    const fieldDidUpdate = FieldBlocks.prototype.componentDidUpdate;
    FieldBlocks.prototype.componentDidUpdate = function (prevProps, ...args) {
        const result = fieldDidUpdate ? fieldDidUpdate.call(this, prevProps, ...args) : undefined;
        if (shouldExpand(prevProps, this.props)) {
            defer(() => expand(this));
        }

        return result;
    };
};
