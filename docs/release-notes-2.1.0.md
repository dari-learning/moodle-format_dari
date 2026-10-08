**Dari Course Format 2.1.0**

Supports Moodle 4.4 to 5.3.

This release rebuilds how Dari creates AI images for course banners and section and activity cards. Images are now specific to each card's topic, planned as a set so they don't repeat, and generated with one high-quality model.

**Better AI images**
- **Planned for the whole course.** Dari plans every card's image before generating any of them. It works out what each section teaches and chooses a distinct, relevant scene for it.
- **Activity cards show what the learner does there.** For example, exam instructions show a candidate preparing for the exam, a practice quiz shows practising, and a forum shows discussion. Dari decides this from the activity's title first, then its description, then its type. An activity card never repeats its section's image.
- **Cards differ from each other.** Dari compares setting, framing, people and lighting across the course, so you don't get a row of near-identical images.
- **Charts, tables and documents are allowed where they suit the topic.** Captions, logos and watermarks are still kept out.
- **One image model, no silent fallbacks.** Google Nano Banana 2.1 is the default; GPT Image 2.5 Sunburst is the alternative. Dari uses only the model you choose, with no fallback to other providers.

**More control for teachers**
- **Same idea again, or a new idea.** When you regenerate a card that already has an image, you can choose to retry the same idea or get a new one. A failed image never changes the idea on its own.
- **Your own description comes first.** It is kept for every later regeneration of that card, and you can edit or clear it at any time.
- **Image plan preview.** Find it in the course's More menu. It shows each card's plan and the exact prompt, and generates no images.

**Reliability and speed**
- **Live progress.** A card shows what is happening (waiting, planning, painting, saving).
- **Long waits are not failures.** A slow job shows "still working" instead of failing, and you can leave the page.
- **No double charging.** Several images can generate at once without duplicate requests.
- **Faster course planning.** The planning step is slimmer.

**Diagnostics**
- **Image generation log.** Shows how long each step of every image took and why any failed. It is on the Image plan preview page and downloadable as CSV.
- **Browser error recording.** Browser errors on Dari pages are recorded for course editors. You can turn diagnostics off in the plugin settings.
- **Privacy.** The privacy API covers the new log, which is kept for 30 days.

**Upgrade notes**
- Image plans made by earlier versions are cleared and planned again automatically.
- Removed settings: image quality, image style, scene writer and prompt building. Dari now always uses the highest-quality option.
