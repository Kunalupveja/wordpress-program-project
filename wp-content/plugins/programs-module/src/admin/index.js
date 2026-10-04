/**
 * React admin panel for the program custom fields.
 *
 * Deliberately built from primitives rather than @wordpress/components so
 * the panel does not inherit the default WP admin field look.
 */

import { render, useState, useCallback, useRef } from '@wordpress/element';
import './style.scss';

const { ProgramsMeta } = window;

let uid = 0;
const nextUid = () => `section-${ ++uid }`;

/**
 * Normalises a server section into a row with client-side bookkeeping.
 *
 * @param {Object} section Raw section.
 * @return {Object} Row with a stable uid.
 */
const toRow = ( section ) => ( {
	uid: nextUid(),
	title: section?.title || '',
	description: section?.description || '',
	image: Number( section?.image ) || 0,
	imageUrl: '',
} );

/**
 * Opens the WordPress media library and resolves with the chosen image.
 *
 * @param {Function} onSelect Called with { id, url }.
 */
const openMediaLibrary = ( onSelect ) => {
	if ( ! window.wp || ! window.wp.media ) {
		return;
	}

	const frame = window.wp.media( {
		title: 'Choose a section image',
		library: { type: 'image' },
		button: { text: 'Use this image' },
		multiple: false,
	} );

	frame.on( 'select', () => {
		const attachment = frame.state().get( 'selection' ).first().toJSON();
		const url =
			attachment.sizes?.medium?.url ||
			attachment.sizes?.thumbnail?.url ||
			attachment.url ||
			'';

		onSelect( { id: Number( attachment.id ) || 0, url } );
	} );

	frame.open();
};

/**
 * A single content section row.
 *
 * @param {Object}  props           Component props.
 * @param {Object}  props.section   The row data.
 * @param {number}  props.index     Position in the list.
 * @param {number}  props.total     Total number of rows.
 * @param {boolean} props.collapsed Whether the body is hidden.
 * @param {Object}  props.callbacks Row callbacks.
 */
const SectionRow = ( { section, index, total, collapsed, callbacks } ) => {
	const { onChange, onRemove, onMove, onToggle, onPickImage } = callbacks;
	const missingTitle = '' === section.title.trim();

	return (
		<li className={ `pm-card${ missingTitle ? ' pm-card--invalid' : '' }` }>
			<div className="pm-card__head">
				<button
					type="button"
					className="pm-card__toggle"
					aria-expanded={ ! collapsed }
					onClick={ onToggle }
				>
					<span className="pm-card__chevron" aria-hidden="true">
						{ collapsed ? '▸' : '▾' }
					</span>
					<span className="pm-card__index">{ index + 1 }</span>
					<span className="pm-card__label">
						{ section.title.trim() || 'Untitled section' }
					</span>
				</button>

				<div className="pm-card__actions">
					<button
						type="button"
						className="pm-btn pm-btn--icon"
						onClick={ () => onMove( -1 ) }
						disabled={ 0 === index }
						title="Move up"
					>
						↑
					</button>
					<button
						type="button"
						className="pm-btn pm-btn--icon"
						onClick={ () => onMove( 1 ) }
						disabled={ index === total - 1 }
						title="Move down"
					>
						↓
					</button>
					<button
						type="button"
						className="pm-btn pm-btn--danger"
						onClick={ onRemove }
						title="Remove section"
					>
						Remove
					</button>
				</div>
			</div>

			{ ! collapsed && (
				<div className="pm-card__body">
					<label
						className="pm-field"
						htmlFor={ `pm-section-title-${ index }` }
					>
						<span className="pm-field__label">Title</span>
						<input
							id={ `pm-section-title-${ index }` }
							type="text"
							className="pm-input"
							value={ section.title }
							onChange={ ( event ) =>
								onChange( { title: event.target.value } )
							}
						/>
					</label>

					{ missingTitle && (
						<p className="pm-error">
							A title is required for this section.
						</p>
					) }

					<label
						className="pm-field"
						htmlFor={ `pm-section-description-${ index }` }
					>
						<span className="pm-field__label">Description</span>
						<textarea
							id={ `pm-section-description-${ index }` }
							className="pm-input pm-input--area"
							rows={ 4 }
							value={ section.description }
							onChange={ ( event ) =>
								onChange( {
									description: event.target.value,
								} )
							}
						/>
					</label>

					<div className="pm-field">
						<span className="pm-field__label">
							Image (optional)
						</span>

						{ section.image ? (
							<div className="pm-media">
								<img
									className="pm-media__preview"
									src={ section.imageUrl }
									alt=""
								/>
								<button
									type="button"
									className="pm-btn"
									onClick={ () => onPickImage() }
								>
									Replace
								</button>
								<button
									type="button"
									className="pm-btn pm-btn--danger"
									onClick={ () =>
										onChange( {
											image: 0,
											imageUrl: '',
										} )
									}
								>
									Remove
								</button>
							</div>
						) : (
							<button
								type="button"
								className="pm-btn"
								onClick={ () => onPickImage() }
							>
								Choose image
							</button>
						) }
					</div>
				</div>
			) }
		</li>
	);
};

/**
 * The panel itself.
 */
const App = () => {
	const [ year, setYear ] = useState( ProgramsMeta?.academicYear || '' );
	const [ duration, setDuration ] = useState( ProgramsMeta?.duration || '' );
	const [ sections, setSections ] = useState(
		( ProgramsMeta?.sections || [] ).map( toRow )
	);
	const [ collapsed, setCollapsed ] = useState( {} );
	const rowsRef = useRef( null );

	const updateSection = useCallback( ( uidToUpdate, patch ) => {
		setSections( ( current ) =>
			current.map( ( row ) =>
				row.uid === uidToUpdate ? { ...row, ...patch } : row
			)
		);
	}, [] );

	const addSection = useCallback( () => {
		setSections( ( current ) => [ ...current, toRow( {} ) ] );
	}, [] );

	const removeSection = useCallback( ( uidToRemove ) => {
		setSections( ( current ) =>
			current.filter( ( row ) => row.uid !== uidToRemove )
		);
	}, [] );

	const moveSection = useCallback( ( uidToMove, offset ) => {
		setSections( ( current ) => {
			const from = current.findIndex( ( row ) => row.uid === uidToMove );
			const to = from + offset;

			if ( from < 0 || to < 0 || to >= current.length ) {
				return current;
			}

			const next = [ ...current ];
			const [ moved ] = next.splice( from, 1 );
			next.splice( to, 0, moved );

			return next;
		} );
	}, [] );

	const toggleSection = useCallback( ( uidToToggle ) => {
		setCollapsed( ( current ) => ( {
			...current,
			[ uidToToggle ]: ! current[ uidToToggle ],
		} ) );
	}, [] );

	const pickImage = useCallback(
		( uidToUpdate ) => {
			openMediaLibrary( ( { id, url } ) =>
				updateSection( uidToUpdate, { image: id, imageUrl: url } )
			);
		},
		[ updateSection ]
	);

	// Only the three documented keys are submitted; UI-only fields such as
	// uid and imageUrl are stripped here.
	const payload = sections.map( ( row ) => ( {
		title: row.title,
		description: row.description,
		image: row.image,
	} ) );

	return (
		<div className="pm">
			<div className="pm-grid">
				<label className="pm-field" htmlFor="pm-academic-year">
					<span className="pm-field__label">Academic year</span>
					<input
						id="pm-academic-year"
						type="text"
						className="pm-input"
						placeholder="2025-26"
						value={ year }
						onChange={ ( event ) => setYear( event.target.value ) }
					/>
					<span className="pm-field__hint">For example 2025-26.</span>
				</label>

				<label className="pm-field" htmlFor="pm-duration">
					<span className="pm-field__label">Duration</span>
					<input
						id="pm-duration"
						type="text"
						className="pm-input"
						placeholder="3 Years"
						value={ duration }
						onChange={ ( event ) =>
							setDuration( event.target.value )
						}
					/>
					<span className="pm-field__hint">For example 3 Years.</span>
				</label>
			</div>

			<div className="pm-sections">
				<div className="pm-sections__head">
					<h3 className="pm-sections__title">Content sections</h3>
					<span className="pm-count">{ sections.length }</span>
				</div>

				{ 0 === sections.length && (
					<p className="pm-empty">
						No content sections yet. Add the first one below.
					</p>
				) }

				<ul className="pm-list" ref={ rowsRef }>
					{ sections.map( ( section, index ) => (
						<SectionRow
							key={ section.uid }
							section={ section }
							index={ index }
							total={ sections.length }
							collapsed={ !! collapsed[ section.uid ] }
							callbacks={ {
								onChange: ( patch ) =>
									updateSection( section.uid, patch ),
								onRemove: () => removeSection( section.uid ),
								onMove: ( offset ) =>
									moveSection( section.uid, offset ),
								onToggle: () => toggleSection( section.uid ),
								onPickImage: () => pickImage( section.uid ),
							} }
						/>
					) ) }
				</ul>

				<button
					type="button"
					className="pm-btn pm-btn--primary"
					onClick={ addSection }
				>
					+ Add section
				</button>
			</div>

			{ /* Submitted with the post; sanitised server-side. */ }
			<input
				type="hidden"
				name={ ProgramsMeta?.fieldYear }
				value={ year }
				readOnly
			/>
			<input
				type="hidden"
				name={ ProgramsMeta?.fieldDur }
				value={ duration }
				readOnly
			/>
			<input
				type="hidden"
				name={ ProgramsMeta?.fieldSec }
				value={ JSON.stringify( payload ) }
				readOnly
			/>
		</div>
	);
};

const root = document.getElementById( 'programs-meta-root' );

if ( root && ProgramsMeta ) {
	render( <App />, root );
}
