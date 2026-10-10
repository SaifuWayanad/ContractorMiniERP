import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, Linking, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type ClientDetail = {
  id: number;
  name: string;
  company: string | null;
  contact_person: string | null;
  phone: string | null;
  email: string | null;
  status: string;
  projects: Array<{ id: number; name: string; project_number: string; status: string }>;
};

export default function ClientDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [client, setClient] = useState<ClientDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [deleting, setDeleting] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get(`/mobile/clients/${id}`);
      setClient(res.data);
    } catch { /* ignore */ } finally { setLoading(false); }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  function handleDelete() {
    Alert.alert(
      'Delete Client',
      `Are you sure you want to delete "${client?.name}"? This cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete', style: 'destructive',
          onPress: async () => {
            setDeleting(true);
            try {
              await api.delete(`/mobile/clients/${id}`);
              router.replace('/(tabs)/clients');
            } catch (err: any) {
              Alert.alert('Error', err?.response?.data?.message ?? 'Failed to delete.');
            } finally { setDeleting(false); }
          },
        },
      ]
    );
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;
  if (!client) return <View style={s.centered}><Text style={{ color: Colors.textMuted }}>Client not found.</Text></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title={client.name}
        subtitle={client.company ?? 'Client Details'}
        right={
          <View style={s.headerBtns}>
            <TouchableOpacity
              onPress={() => router.push({ pathname: '/(tabs)/clients/form', params: { id: String(client.id) } })}
              style={s.editBtn}
            >
              <Ionicons name="create-outline" size={20} color={Colors.primary} />
            </TouchableOpacity>
            <TouchableOpacity onPress={handleDelete} style={s.deleteBtn} disabled={deleting}>
              <Ionicons name="trash-outline" size={20} color={Colors.white} />
            </TouchableOpacity>
          </View>
        }
      />
      <ScrollView contentContainerStyle={s.content} showsVerticalScrollIndicator={false}>
          <Card title="Contact Info">
            {client.company && <View style={s.row}><Text style={s.label}>Company</Text><Text style={s.value}>{client.company}</Text></View>}
            {client.contact_person && <View style={s.row}><Text style={s.label}>Contact</Text><Text style={s.value}>{client.contact_person}</Text></View>}
            {client.phone && (
              <View style={s.row}>
                <Text style={s.label}>Phone</Text>
                <TouchableOpacity onPress={() => Linking.openURL(`tel:${client.phone}`)}>
                  <Text style={[s.value, { color: Colors.info }]}>{client.phone}</Text>
                </TouchableOpacity>
              </View>
            )}
            {client.email && (
              <View style={s.row}>
                <Text style={s.label}>Email</Text>
                <TouchableOpacity onPress={() => Linking.openURL(`mailto:${client.email}`)}>
                  <Text style={[s.value, { color: Colors.info }]}>{client.email}</Text>
                </TouchableOpacity>
              </View>
            )}
            <View style={s.row}><Text style={s.label}>Status</Text><Badge status={client.status} /></View>
          </Card>

          {client.projects.length > 0 && (
            <Card title={`Projects (${client.projects.length})`}>
              {client.projects.map((p, i) => (
                <TouchableOpacity
                  key={p.id}
                  style={[s.projRow, i > 0 && s.border]}
                  onPress={() => router.push(`/(tabs)/projects/${p.id}`)}
                  activeOpacity={0.7}
                >
                  <View style={{ flex: 1 }}>
                    <Text style={s.projName} numberOfLines={1}>{p.name}</Text>
                    <Text style={s.projNum}>{p.project_number}</Text>
                  </View>
                  <Badge status={p.status} />
                  <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} style={{ marginLeft: 8 }} />
                </TouchableOpacity>
              ))}
            </Card>
          )}
        </ScrollView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  headerBtns: { flexDirection: 'row', gap: 8 },
  editBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.accent,
    alignItems: 'center', justifyContent: 'center',
  },
  deleteBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.danger,
    alignItems: 'center', justifyContent: 'center',
  },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  label: { fontSize: 13, color: Colors.textSecondary },
  value: { fontSize: 13, fontWeight: '600', color: Colors.text },
  projRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10 },
  border: { borderTopWidth: 1, borderTopColor: Colors.border },
  projName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  projNum: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
});
