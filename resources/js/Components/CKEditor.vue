<script setup>

import { computed, ref, onMounted, watch } from 'vue';
import { Ckeditor, useCKEditorCloud } from '@ckeditor/ckeditor5-vue';

const LICENSE_KEY = import.meta.env.VITE_CKEDITOR_LICENSE_KEY;

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
});
const emit = defineEmits(['update:modelValue']);

// 2. Gunakan ref lokal untuk data editor, disinkronkan dengan prop
const editorData = ref(props.modelValue);

// Jika data dari parent (form) berubah, update editor
watch(() => props.modelValue, (newValue) => {
    if (newValue !== editorData.value) {
        editorData.value = newValue;
    }
});
// Jika data di editor berubah, update parent (form)
watch(editorData, (newValue) => {
    emit('update:modelValue', newValue);
});


const cloud = useCKEditorCloud({ version: '46.0.1' });

const isLayoutReady = ref(false);

const editor = computed(() => {
    return cloud.data.value ? cloud.data.value.CKEditor.ClassicEditor : null;
});

const config = computed(() => {
	if (!isLayoutReady.value) {
		return null;
	}

	if (!cloud.data.value) {
		return null;
	}

	const {
		Alignment,
		AutoImage,
		AutoLink,
		Autosave,
		BalloonToolbar,
		Base64UploadAdapter,
		BlockQuote,
		Bold,
		Bookmark,
		CloudServices,
		Code,
		Emoji,
		Essentials,
		FindAndReplace,
		FontBackgroundColor,
		FontColor,
		FontSize,
		Heading,
		Highlight,
		ImageBlock,
		ImageCaption,
		ImageInline,
		ImageInsert,
		ImageInsertViaUrl,
		ImageResize,
		ImageStyle,
		ImageTextAlternative,
		ImageToolbar,
		ImageUpload,
		Indent,
		IndentBlock,
		Italic,
		Link,
		LinkImage,
		List,
		ListProperties,
		MediaEmbed,
		Mention,
		Paragraph,
		PasteFromOffice,
		RemoveFormat,
		Strikethrough,
		Subscript,
		Superscript,
		TodoList,
		Underline
	} = cloud.data.value.CKEditor;

	return {
		toolbar: {
			items: [
				'undo',
				'redo',
				'|',
				'findAndReplace',
				'|',
				'heading',
				'|',
				'fontSize',
				'fontColor',
				'fontBackgroundColor',
				'|',
				'bold',
				'italic',
				'underline',
				'strikethrough',
				'subscript',
				'superscript',
				'code',
				'removeFormat',
				'|',
				'emoji',
				'link',
				'bookmark',
				'insertImage',
				'mediaEmbed',
				'highlight',
				'blockQuote',
				'|',
				'alignment',
				'|',
				'bulletedList',
				'numberedList',
				'todoList',
				'outdent',
				'indent'
			],
			shouldNotGroupWhenFull: false
		},
		plugins: [
			Alignment,
			AutoImage,
			AutoLink,
			Autosave,
			BalloonToolbar,
			Base64UploadAdapter,
			BlockQuote,
			Bold,
			Bookmark,
			CloudServices,
			Code,
			Emoji,
			Essentials,
			FindAndReplace,
			FontBackgroundColor,
			FontColor,
			FontSize,
			Heading,
			Highlight,
			ImageBlock,
			ImageCaption,
			ImageInline,
			ImageInsert,
			ImageInsertViaUrl,
			ImageResize,
			ImageStyle,
			ImageTextAlternative,
			ImageToolbar,
			ImageUpload,
			Indent,
			IndentBlock,
			Italic,
			Link,
			LinkImage,
			List,
			ListProperties,
			MediaEmbed,
			Mention,
			Paragraph,
			PasteFromOffice,
			RemoveFormat,
			Strikethrough,
			Subscript,
			Superscript,
			TodoList,
			Underline
		],
		balloonToolbar: ['bold', 'italic', '|', 'link', 'insertImage', '|', 'bulletedList', 'numberedList'],

		fontFamily: {
			supportAllValues: true
		},
		fontSize: {
			options: [10, 12, 14, 'default', 18, 20, 22, 24, 26, 28, 30, 32],
			supportAllValues: true
		},
		heading: {
			options: [
				{
					model: 'paragraph',
					title: 'Paragraph',
					class: 'ck-heading_paragraph'
				},
				{
					model: 'heading1',
					view: 'h1',
					title: 'Heading 1',
					class: 'ck-heading_heading1'
				},
				{
					model: 'heading2',
					view: 'h2',
					title: 'Heading 2',
					class: 'ck-heading_heading2'
				},
				{
					model: 'heading3',
					view: 'h3',
					title: 'Heading 3',
					class: 'ck-heading_heading3'
				},
				{
					model: 'heading4',
					view: 'h4',
					title: 'Heading 4',
					class: 'ck-heading_heading4'
				},
				{
					model: 'heading5',
					view: 'h5',
					title: 'Heading 5',
					class: 'ck-heading_heading5'
				},
				{
					model: 'heading6',
					view: 'h6',
					title: 'Heading 6',
					class: 'ck-heading_heading6'
				}
			]
		},
		htmlSupport: {
			allow: [
				{
					name: /^.*$/,
					styles: true,
					attributes: true,
					classes: true
				}
			]
		},
		licenseKey: LICENSE_KEY,
		link: {
			addTargetToExternalLinks: true,
			defaultProtocol: 'https://',
			decorators: {
				toggleDownloadable: {
					mode: 'manual',
					label: 'Downloadable',
					attributes: {
						download: 'file'
					}
				}
			}
		},
		list: {
			properties: {
				styles: true,
				startIndex: true,
				reversed: true
			}
		},
		placeholder: 'Type or paste your content here!',

	};
});

onMounted(() => {
	isLayoutReady.value = true;
});
</script>


<template>
	<div class="main-container">
		<div
			class="editor-container editor-container_classic-editor editor-container_include-style editor-container_include-block-toolbar"
			ref="editorContainerElement"
		>
			<div class="editor-container__editor">
				<div ref="editorElement">
					<ckeditor v-if="editor" v-model="editorData" :editor="editor" :config="config" />
                    <div v-else>
                        Loading Editor...
                    </div>
				</div>

			</div>
		</div>
	</div>
</template>

