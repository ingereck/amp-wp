/**
 * Custom component for BlockDropZone for being able to position inner blocks via drag and drop.
 * Parts of this are taken from the original BlockDropZone component.
 */

/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import {
	DropZone,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getPercentageFromPixels } from '../helpers';

const wrapperElSelector = 'div[data-amp-selected="parent"] .editor-inner-blocks';

class BlockDropZone extends Component {
	constructor() {
		super( ...arguments );

		this.onDrop = this.onDrop.bind( this );
	}

	onDrop( event ) {
		const { updateBlockAttributes, srcClientId } = this.props;

		const elementId = `block-${ srcClientId }`;
		const cloneElementId = `clone-block-${ srcClientId }`;
		const element = document.getElementById( elementId );
		const clone = document.getElementById( cloneElementId );

		// Get the editor wrapper element for calculating the width and height.
		const wrapperEl = document.querySelector( wrapperElSelector );
		if ( ! element || ! clone || ! wrapperEl ) {
			event.preventDefault();
			return;
		}

		// We have to remove the rotation for getting accurate position.
		clone.parentNode.style.visibility = 'hidden';
		clone.parentNode.style.transform = 'none';
		const clonePosition = clone.getBoundingClientRect();
		const wrapperPosition = wrapperEl.getBoundingClientRect();

		// We will set the new position based on where the clone was moved to, with reference being the wrapper element.
		// Lets take the % based on the wrapper for top and left.
		// @todo this 5px is compensating for the dragging border, however, it should only be applied for Text block and 5 should be a constant.
		updateBlockAttributes( srcClientId, {
			positionLeft: getPercentageFromPixels( 'x', clonePosition.left - wrapperPosition.left + 5 ),
			positionTop: getPercentageFromPixels( 'y', clonePosition.top - wrapperPosition.top + 5 ),
		} );
	}

	render() {
		return (
			<DropZone
				className="editor-block-drop-zone"
				onDrop={ this.onDrop }
			/>
		);
	}
}

BlockDropZone.propTypes = {
	updateBlockAttributes: PropTypes.func,
	srcClientId: PropTypes.string,
};

export default withDispatch( ( dispatch ) => {
	const { updateBlockAttributes } = dispatch( 'core/block-editor' );

	return {
		updateBlockAttributes( ...args ) {
			updateBlockAttributes( ...args );
		},
	};
} )( BlockDropZone );
