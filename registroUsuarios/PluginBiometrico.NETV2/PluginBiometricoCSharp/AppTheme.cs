using System.Drawing;
using System.Windows.Forms;

namespace PluginBiometricoCSharp
{
    public static class AppTheme
    {
        public static readonly Color Background = Color.FromArgb(11, 22, 34);
        public static readonly Color Surface = Color.FromArgb(18, 34, 50);
        public static readonly Color SurfaceSoft = Color.FromArgb(24, 44, 63);
        public static readonly Color Accent = Color.FromArgb(18, 210, 230);
        public static readonly Color AccentDark = Color.FromArgb(9, 145, 165);
        public static readonly Color Text = Color.FromArgb(240, 247, 250);
        public static readonly Color MutedText = Color.FromArgb(154, 176, 190);
        public static readonly Color Error = Color.FromArgb(255, 112, 112);

        public static readonly Font TitleFont = new Font("Segoe UI Semibold", 15F, FontStyle.Bold);
        public static readonly Font LabelFont = new Font("Segoe UI", 9F, FontStyle.Regular);
        public static readonly Font BodyFont = new Font("Segoe UI", 9F, FontStyle.Regular);
        public static readonly Font ButtonFont = new Font("Segoe UI Semibold", 9F, FontStyle.Bold);

        public static void ApplyForm(Form form, Size size)
        {
            form.BackColor = Background;
            form.ClientSize = size;
            form.Font = BodyFont;
            form.ForeColor = Text;
            form.FormBorderStyle = FormBorderStyle.FixedSingle;
            form.MaximizeBox = false;
            form.StartPosition = FormStartPosition.CenterScreen;
        }

        public static Label CreateTitle(string text, int left, int top, int width)
        {
            return new Label
            {
                AutoSize = false,
                Left = left,
                Top = top,
                Width = width,
                Height = 30,
                Text = text,
                Font = TitleFont,
                ForeColor = Text,
                BackColor = Color.Transparent
            };
        }

        public static Label CreateLabel(string text, int left, int top, int width)
        {
            return new Label
            {
                AutoSize = false,
                Left = left,
                Top = top,
                Width = width,
                Height = 18,
                Text = text,
                Font = LabelFont,
                ForeColor = MutedText,
                BackColor = Color.Transparent
            };
        }

        public static TextBox CreateTextBox(int left, int top, int width)
        {
            return new TextBox
            {
                Left = left,
                Top = top,
                Width = width,
                Height = 24,
                BorderStyle = BorderStyle.FixedSingle,
                BackColor = SurfaceSoft,
                ForeColor = Text,
                Font = BodyFont
            };
        }

        public static TextBox CreateStatusBox(int left, int top, int width, int height)
        {
            return new TextBox
            {
                Left = left,
                Top = top,
                Width = width,
                Height = height,
                Multiline = true,
                ReadOnly = true,
                BorderStyle = BorderStyle.FixedSingle,
                BackColor = Surface,
                ForeColor = Text,
                Font = BodyFont,
                ScrollBars = ScrollBars.Vertical
            };
        }

        public static ComboBox CreateComboBox(int left, int top, int width)
        {
            return new ComboBox
            {
                Left = left,
                Top = top,
                Width = width,
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor = SurfaceSoft,
                ForeColor = Text,
                Font = BodyFont,
                FlatStyle = FlatStyle.Flat
            };
        }

        public static Button CreatePrimaryButton(string text, int left, int top, int width)
        {
            var button = new Button
            {
                Left = left,
                Top = top,
                Width = width,
                Height = 34,
                Text = text,
                Font = ButtonFont,
                BackColor = Accent,
                ForeColor = Color.FromArgb(4, 22, 30),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            button.FlatAppearance.BorderSize = 0;
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(73, 232, 245);
            button.FlatAppearance.MouseDownBackColor = AccentDark;
            return button;
        }

        public static Panel CreateHeaderPanel(int width, string title, string subtitle)
        {
            var panel = new Panel
            {
                Left = 0,
                Top = 0,
                Width = width,
                Height = 86,
                BackColor = Surface
            };

            var icon = new PictureBox
            {
                Left = 20,
                Top = 18,
                Width = 42,
                Height = 42,
                Image = AppIcons.MainIcon.ToBitmap(),
                SizeMode = PictureBoxSizeMode.StretchImage
            };

            var titleLabel = new Label
            {
                Left = 74,
                Top = 17,
                Width = width - 92,
                Height = 28,
                Text = title,
                Font = TitleFont,
                ForeColor = Text,
                BackColor = Color.Transparent
            };

            var subtitleLabel = new Label
            {
                Left = 75,
                Top = 47,
                Width = width - 92,
                Height = 20,
                Text = subtitle,
                Font = LabelFont,
                ForeColor = MutedText,
                BackColor = Color.Transparent
            };

            panel.Controls.Add(icon);
            panel.Controls.Add(titleLabel);
            panel.Controls.Add(subtitleLabel);
            return panel;
        }
    }
}
